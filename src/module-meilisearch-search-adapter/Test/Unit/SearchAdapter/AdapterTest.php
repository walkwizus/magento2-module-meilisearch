<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Test\Unit\SearchAdapter;

use Magento\AdvancedSearch\Model\Client\ClientException;
use Magento\Catalog\Model\Layer\Filter\Price\Range as PriceRange;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Search\Dynamic\Algorithm\AlgorithmInterface;
use Magento\Framework\Search\Dynamic\Algorithm\Repository as AlgorithmRepository;
use Magento\Framework\Search\Dynamic\EntityStorage;
use Magento\Framework\Search\Dynamic\EntityStorageFactory;
use Magento\Framework\Search\Request;
use Magento\Framework\Search\Request\Aggregation\DynamicBucket;
use Magento\Framework\Search\Request\Aggregation\TermBucket;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\Filter\BoolExpression as BoolFilter;
use Magento\Framework\Search\Request\Filter\Range as RangeFilter;
use Magento\Framework\Search\Request\Filter\Term as TermFilter;
use Magento\Framework\Search\Request\Filter\Wildcard as WildcardFilter;
use Magento\Framework\Search\Request\Query\BoolExpression as BoolQuery;
use Magento\Framework\Search\Request\Query\Filter as FilterQuery;
use Magento\Framework\Search\Request\Query\MatchQuery;
use Magento\Framework\Search\Request\QueryInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Meilisearch\Client;
use Meilisearch\Contracts\Http;
use Meilisearch\Endpoints\Index;
use Meilisearch\Exceptions\ApiException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchSearchAdapter\Model\IndexSettings;
use Walkwizus\MeilisearchSearchAdapter\SearchAdapter\Adapter;
use Walkwizus\MeilisearchSearchAdapter\SearchAdapter\Dynamic\PriceDataProvider;

class AdapterTest extends TestCase
{
    private \Closure $post;
    private ResponseFactory&MockObject $responseFactory;
    private AlgorithmRepository&MockObject $algorithmRepository;
    private LoggerInterface&MockObject $logger;
    private ?string $searchedIndex = null;
    private array $sent = [];
    private array $responseData = [];
    private Adapter $adapter;
    private ProductCollection&MockObject $productCollection;
    private int $customerGroupId = 0;
    private array $posts = [];
    private array $filterable = ['category_ids', 'visibility_ids', 'color_ids', 'material', 'price_0', 'id', 'sku'];
    private array $sortable = ['position_category_2', 'position_category_4', 'name', 'color', 'price_0', 'id'];
    private array $searchable = ['sku', 'name', 'description'];

    protected function setUp(): void
    {
        $this->post = function (string $path, $body) {
            $this->searchedIndex = $path;
            $this->sent = (array)$body;
            $this->posts[] = $this->sent;

            return ['hits' => [['id' => 10, '_rankingScore' => 0.9], ['id' => 20]], 'totalHits' => 2, 'facetDistribution' => [
                'color_ids' => ['49' => 2, '50' => 1],
                'price_0' => ['12.5' => 1, '45' => 1],
            ]];
        };
        $http = $this->createMock(Http::class);
        $http->method('post')->willReturnCallback(fn (...$args) => ($this->post)(...$args));

        $client = $this->createMock(Client::class);
        $client->method('index')->willReturnCallback(fn (string $uid) => new Index($http, $uid));
        $client->method('multiSearch')->willReturnCallback(fn (array $queries) => [
            'results' => array_map(fn ($query) => ($this->post)('/multi-search', $query->toArray()), $queries),
        ]);
        $connectionManager = $this->createMock(ConnectionManager::class);
        $connectionManager->method('getConnection')->willReturn($client);

        $indexNameResolver = new SearchIndexNameResolver($this->createMock(ServerSettings::class));

        $scope = $this->createMock(Store::class);
        $scope->method('getId')->willReturn(1);
        $scope->method('getRootCategoryId')->willReturn(2);
        $scopeResolver = $this->createMock(ScopeResolverInterface::class);
        $scopeResolver->method('getScope')->willReturn($scope);

        $this->responseFactory = $this->createMock(ResponseFactory::class);
        $this->responseFactory->method('create')->willReturnCallback(function (array $data) {
            $this->responseData = $data;

            return $this->createMock(QueryResponse::class);
        });

        $optionAttribute = $this->createMock(AbstractAttribute::class);
        $optionAttribute->method('usesSource')->willReturn(true);
        $textAttribute = $this->createMock(AbstractAttribute::class);
        $textAttribute->method('usesSource')->willReturn(false);
        $eavConfig = $this->createMock(EavConfig::class);
        $eavConfig->method('getAttribute')->willReturnCallback(
            fn ($entity, $code) => in_array($code, ['color', 'visibility'], true) ? $optionAttribute : $textAttribute
        );

        $customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerGroupId'])
            ->getMock();
        $customerSession->method('getCustomerGroupId')->willReturnCallback(fn () => $this->customerGroupId);

        $entityStorageFactory = $this->createMock(EntityStorageFactory::class);
        $entityStorageFactory->method('create')->willReturnCallback(fn ($ids) => new EntityStorage($ids));

        $this->algorithmRepository = $this->createMock(AlgorithmRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $indexSettings = $this->createMock(IndexSettings::class);
        $indexSettings->method('isFilterable')->willReturnCallback(fn ($index, $field) => in_array($field, $this->filterable, true));
        $indexSettings->method('isSortable')->willReturnCallback(fn ($index, $field) => in_array($field, $this->sortable, true));
        $indexSettings->method('isSearchable')->willReturnCallback(fn ($index, $field) => in_array($field, $this->searchable, true));

        $this->productCollection = $this->createMock(ProductCollection::class);
        $this->productCollection->method('setStoreId')->willReturnSelf();
        $this->productCollection->method('addAttributeToFilter')->willReturnSelf();
        $productCollectionFactory = $this->createMock(ProductCollectionFactory::class);
        $productCollectionFactory->method('create')->willReturn($this->productCollection);

        $this->adapter = new Adapter(
            $connectionManager,
            $indexNameResolver,
            $scopeResolver,
            $this->responseFactory,
            $this->algorithmRepository,
            $entityStorageFactory,
            $this->createMock(PriceRange::class),
            $eavConfig,
            $customerSession,
            $this->logger,
            $indexSettings,
            $productCollectionFactory
        );
    }

    public function testCategoryRequestBecomesOneFilteredSortedSearch(): void
    {
        $query = new BoolQuery('container', 1, [
            'category' => new FilterQuery('category', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('category_filter', 4, 'category_ids')),
            'visibility' => new FilterQuery('visibility', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('visibility_filter', ['in' => [2, 4]], 'visibility')),
            'color' => new FilterQuery('color', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('color_filter', '49', 'color')),
            'price' => new FilterQuery('price', 1, FilterQuery::REFERENCE_FILTER, new RangeFilter('price_filter', 'price', 10, null)),
        ]);

        $this->adapter->query($this->request($query, [
            new TermBucket('color_bucket', 'color', []),
            new TermBucket('material_bucket', 'material', []),
        ], [['field' => 'position', 'direction' => 'DESC'], ['field' => 'name', 'direction' => 'ASC']], 12, 12));

        self::assertSame('indexes/catalog_product_1/search', ltrim((string)$this->searchedIndex, '/'));
        self::assertSame(
            '(category_ids = "4") AND (visibility_ids IN ["2", "4"]) AND (color_ids = "49") AND (price_0 >= "10")',
            $this->sent['filter']
        );
        self::assertSame(['position_category_4:desc', 'name:asc'], $this->sent['sort']);
        self::assertSame(['color_ids', 'material'], $this->sent['facets']);
        self::assertSame(2, $this->sent['page']);
        self::assertSame(12, $this->sent['hitsPerPage']);
        self::assertSame(['id'], $this->sent['attributesToRetrieve']);

        self::assertSame([['_id' => 10, '_score' => 0.9], ['_id' => 20, '_score' => 0]], $this->responseData['documents']);
        self::assertSame(2, $this->responseData['total']);
        self::assertSame(
            [49 => ['value' => 49, 'count' => 2], 50 => ['value' => 50, 'count' => 1]],
            $this->responseData['aggregations']['color_bucket']
        );
        // Meilisearch leaves out empty distributions; Magento still expects the bucket
        self::assertSame([], $this->responseData['aggregations']['material_bucket']);
    }

    public function testMatchBecomesSearchTextAndRelevanceIsLeftToRanking(): void
    {
        $query = new BoolQuery('quick_search_container', 1, [], [
            'search' => new MatchQuery('search', 'jacket', 1, [['field' => '*']]),
            'sku' => new FilterQuery('sku', 1, FilterQuery::REFERENCE_FILTER, new WildcardFilter('sku_filter', 'MJ01', 'sku')),
        ], [
            'excluded' => new FilterQuery('excluded', 1, FilterQuery::REFERENCE_FILTER, new BoolFilter('bool', [
                new TermFilter('a', 1, 'id'),
            ], [], [])),
        ]);

        $this->adapter->query($this->request($query, [], [['field' => 'relevance', 'direction' => 'DESC']], 0, 10));

        self::assertSame('jacket', $this->sent['q']);
        self::assertSame('((id IN [10, 20])) AND (NOT ((id = "1")))', $this->sent['filter']);
        self::assertArrayNotHasKey('sort', $this->sent);
        self::assertSame(1, $this->sent['page']);
    }

    public function testOutOfStockToTheBottomSortsOnTheStockFlag(): void
    {
        $this->sortable[] = 'stock.is_in_stock';

        $this->adapter->query($this->request(new MatchQuery('search', 'jacket', 1, [['field' => '*']]), [], [
            ['field' => 'is_out_of_stock', 'direction' => 'ASC'],
            ['field' => 'relevance', 'direction' => 'DESC'],
            ['field' => 'entity_id', 'direction' => 'DESC'],
        ], 0, 10));

        self::assertSame(['stock.is_in_stock:desc'], $this->sent['sort']);
    }

    public function testStringValuesAreQuoted(): void
    {
        $query = new FilterQuery('sku', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('sku_filter', ['in' => ['24-MB01', 'a "b"']], 'sku'));

        $this->adapter->query($this->request($query, [], [], 0, 10));

        self::assertSame('sku IN ["24-MB01", "a \"b\""]', $this->sent['filter']);
    }

    public function testValuesMeilisearchCannotParseUnquotedOrJsonCannotEncodeAreStillQuoted(): void
    {
        $query = new FilterQuery('sku', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('sku_filter', ['in' => ['+1', "a\xFFb"]], 'sku'));

        $this->adapter->query($this->request($query, [], [], 0, 10));

        self::assertSame("sku IN [\"+1\", \"a\u{FFFD}b\"]", $this->sent['filter']);
    }

    public function testDynamicBucketIsBuiltFromThePriceFacet(): void
    {
        $algorithm = $this->createMock(AlgorithmInterface::class);
        $algorithm->method('getItems')->willReturn([
            ['from' => 0, 'to' => 20, 'count' => 1],
            ['from' => 40, 'to' => 60, 'count' => 1],
        ]);
        $this->algorithmRepository->expects(self::once())
            ->method('get')
            ->with('auto', self::callback(fn ($data) => $data['dataProvider'] instanceof PriceDataProvider))
            ->willReturn($algorithm);

        $query = new FilterQuery('category', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('category_filter', 4, 'category_ids'));
        $this->adapter->query($this->request($query, [new DynamicBucket('price_bucket', 'price', 'auto')], [], 0, 12));

        self::assertSame(['price_0'], $this->sent['facets']);
        self::assertSame([
            '0_20' => ['value' => '0_20', 'from' => 0, 'to' => 20, 'count' => 1],
            '40_60' => ['value' => '40_60', 'from' => 40, 'to' => 60, 'count' => 1],
        ], $this->responseData['aggregations']['price_bucket']);
    }

    public function testFieldsTheIndexCannotUseAreSkippedInsteadOfFailingTheSearch(): void
    {
        $this->customerGroupId = 4;
        $query = new BoolQuery('container', 1, [
            'category' => new FilterQuery('category', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('category_filter', 4, 'category_ids')),
            'excluded' => new FilterQuery('excluded', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('special_filter', ['neq' => 5], 'special_price')),
        ]);

        $this->adapter->query($this->request($query, [
            new DynamicBucket('price_bucket', 'price', 'auto'),
            new TermBucket('special_price_bucket', 'special_price', []),
            new TermBucket('color_bucket', 'color', []),
        ], [['field' => 'special_price', 'direction' => 'ASC'], ['field' => 'price', 'direction' => 'ASC']], 0, 12));

        // price_4: a customer group created after the last full reindex
        self::assertSame(['color_ids'], $this->sent['facets']);
        self::assertSame('(category_ids = "4")', $this->sent['filter']);
        self::assertArrayNotHasKey('sort', $this->sent);
        self::assertSame([], $this->responseData['aggregations']['price_bucket']);
        self::assertSame([], $this->responseData['aggregations']['special_price_bucket']);
    }

    public function testFilterOnAFieldTheIndexLacksMatchesNothing(): void
    {
        $this->customerGroupId = 4;
        $query = new BoolQuery('container', 1, [
            'price' => new FilterQuery('price', 1, FilterQuery::REFERENCE_FILTER, new RangeFilter('price_filter', 'price', 10, 20)),
            'special' => new FilterQuery('special', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('special_filter', '5', 'special_price')),
        ]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        self::assertSame('(id IN []) AND (id IN [])', $this->sent['filter']);
    }

    public function testNegatedConditions(): void
    {
        $query = new BoolQuery('container', 1, [
            'a' => new FilterQuery('a', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('a', ['neq' => '24-MB01'], 'sku')),
            'b' => new FilterQuery('b', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('b', ['nin' => [1, 2]], 'id')),
        ]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        self::assertSame('(NOT (sku = "24-MB01")) AND (NOT (id IN ["1", "2"]))', $this->sent['filter']);
    }

    public function testUrlKeyIsResolvedToProductIds(): void
    {
        $this->productCollection->expects(self::once())->method('addAttributeToFilter')
            ->with('url_key', ['in' => ['beaumont-summit-kit']]);
        $this->productCollection->method('getAllIds')->willReturn(['1', '9']);
        $query = new FilterQuery('url_key', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('url_key_filter', ['eq' => 'beaumont-summit-kit'], 'url_key'));

        $this->adapter->query($this->request($query, [], [], 0, 12));

        self::assertSame('id IN [1, 9]', $this->sent['filter']);
    }

    public function testRelevanceEndsTheSortAndOptionAttributesSortByLabel(): void
    {
        $search = new MatchQuery('search', 'tank', 1, [['field' => '*']]);

        $this->adapter->query($this->request($search, [], [
            ['field' => 'relevance', 'direction' => 'DESC'],
            ['field' => 'entity_id', 'direction' => 'ASC'],
        ], 0, 12));
        self::assertArrayNotHasKey('sort', $this->sent);

        $this->adapter->query($this->request($search, [], [
            ['field' => 'color', 'direction' => 'ASC'],
            ['field' => 'position', 'direction' => 'ASC'],
            ['field' => 'entity_id', 'direction' => 'DESC'],
        ], 0, 12));
        self::assertSame(['color:asc', 'position_category_2:asc', 'id:desc'], $this->sent['sort']);
    }

    public function testSearchTextIsDeduplicatedAndStrippedOfQuerySyntax(): void
    {
        $query = new BoolQuery('quick_search_container', 1, [], [
            'search' => new MatchQuery('search', '-jacket "summit kit"', 1, [['field' => '*']]),
            'partial_search' => new MatchQuery('partial_search', '-jacket "summit kit"', 1, [['field' => '*'], ['field' => 'name']]),
        ]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        self::assertSame('jacket summit kit', $this->sent['q']);
        self::assertArrayNotHasKey('attributesToSearchOn', $this->sent);
    }

    public function testFullyQuotedSearchTextStaysAPhrase(): void
    {
        $this->adapter->query($this->request(new MatchQuery('search', '"summit kit"', 1, [['field' => '*']]), [], [], 0, 12));

        self::assertSame('"summit kit"', $this->sent['q']);
    }

    public function testTextWithNothingToMatchReturnsNoResultsWithoutSearching(): void
    {
        $query = new MatchQuery('search', '" * \\', 1, [['field' => '*']]);

        $this->adapter->query($this->request($query, [new TermBucket('color_bucket', 'color', [])], [], 0, 12));

        self::assertNull($this->searchedIndex);
        self::assertSame([], $this->responseData['documents']);
        self::assertSame(0, $this->responseData['total']);
        self::assertSame([], $this->responseData['aggregations']['color_bucket']);
    }

    public function testFieldSpecificTextIsAConditionOfItsOwn(): void
    {
        $query = new BoolQuery('graphql_product_search', 1, [
            'search' => new MatchQuery('search', 'jacket', 1, [['field' => '*']]),
            'name' => new MatchQuery('name', 'Beaumont Zzzqqq', 1, [['field' => 'name']]),
            'description' => new MatchQuery('description', 'summit kit', 1, [['field' => 'description'], ['field' => 'name'], ['field' => 'meta_title']]),
            'sku' => new FilterQuery('sku', 1, FilterQuery::REFERENCE_FILTER, new WildcardFilter('sku_filter', 'MJ01', 'sku')),
        ]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        $lookups = array_map(fn ($post) => [$post['q'], $post['attributesToSearchOn'], $post['matchingStrategy']], array_slice($this->posts, 0, -1));
        self::assertSame([
            ['Beaumont', ['name'], 'all'],
            ['Zzzqqq', ['name'], 'all'],
            ['summit', ['description', 'name'], 'all'],
            ['kit', ['description', 'name'], 'all'],
            ['MJ01', ['sku'], 'all'],
        ], $lookups);
        self::assertSame('jacket', $this->sent['q']);
        self::assertArrayNotHasKey('attributesToSearchOn', $this->sent);
        self::assertSame('(id IN [10, 20]) AND (id IN [10, 20]) AND (id IN [10, 20])', $this->sent['filter']);
    }

    public function testPhrasePrefixMatchNeedsEveryWord(): void
    {
        $query = new MatchQuery('name', 'summit kit', 1, [['field' => 'name', 'matchCondition' => 'match_phrase_prefix']]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        self::assertSame('summit kit', $this->posts[0]['q']);
        self::assertSame('id IN [10, 20]', $this->sent['filter']);
    }

    public function testFieldTextIsSplitIntoAtMostTenWordsAndLookedUpInOneCall(): void
    {
        $words = implode(' ', array_map(fn ($n) => 'w' . $n, range(1, 2000)));
        $query = new BoolQuery('graphql_product_search', 1, [
            'name' => new MatchQuery('name', 'Duffle-Bag & . ' . $words, 1, [['field' => 'name']]),
            'sku' => new MatchQuery('sku', '. &', 1, [['field' => 'sku']]),
        ]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        $lookups = array_slice($this->posts, 0, -1);
        self::assertSame(['Duffle', 'Bag', 'w1', 'w2', 'w3', 'w4', 'w5', 'w6', 'w7', 'w8'], array_column($lookups, 'q'));
        self::assertSame([1000], array_unique(array_column($lookups, 'limit')));
        self::assertSame('(id IN [10, 20]) AND (id IN [])', $this->sent['filter']);
    }

    public function testWordsSplitLikeTheCoreAnalyser(): void
    {
        $value = "Women's T-Shirt Hoodie_Blue 3.5mm e-mail TEE tee e\u{301}";
        $this->adapter->query($this->request(new MatchQuery('name', $value, 1, [['field' => 'name']]), [], [], 0, 12));

        self::assertSame(
            ["Women's", '"T"', 'Shirt', 'Hoodie_Blue', '3.5mm', '"e"', 'mail', 'TEE', "\"e\u{301}\""],
            array_column(array_slice($this->posts, 0, 9), 'q')
        );
    }

    public function testPhrasePrefixKeepsItsLastWordAPrefix(): void
    {
        $query = new MatchQuery('name', 'Hero H', 1, [['field' => 'name', 'matchCondition' => 'match_phrase_prefix']]);

        $this->adapter->query($this->request($query, [], [], 0, 12));

        self::assertSame('Hero H', $this->posts[0]['q']);
    }

    public function testLongRunsOfSeparatorsStillSplit(): void
    {
        $value = 'Duffle' . str_repeat(' ', 100000) . 'Bag';
        $this->adapter->query($this->request(new MatchQuery('name', $value, 1, [['field' => 'name']]), [], [], 0, 12));

        self::assertSame(['Duffle', 'Bag'], array_column(array_slice($this->posts, 0, 2), 'q'));
    }

    public function testInvalidUtf8MatchesNothing(): void
    {
        $this->adapter->query($this->request(new MatchQuery('name', "\xFF", 1, [['field' => 'name']]), [], [], 0, 12));

        self::assertSame('id IN []', $this->sent['filter']);
    }

    public function testTruncatedLookupsAreLoggedOncePerRequest(): void
    {
        $this->post = fn () => ['hits' => [['id' => 10]], 'estimatedTotalHits' => 20000];
        $this->logger->expects(self::once())->method('warning')->with(self::stringContains('"blue", "jacket"'));

        $this->adapter->query($this->request(new MatchQuery('name', 'blue jacket', 1, [['field' => 'name']]), [], [], 0, 12));
    }

    public function testTextOnAFieldTheIndexCannotSearchMatchesNothing(): void
    {
        $this->adapter->query($this->request(new MatchQuery('meta_title', 'jacket', 1, [['field' => 'meta_title']]), [], [], 0, 12));

        self::assertCount(1, $this->posts);
        self::assertSame('id IN []', $this->sent['filter']);
    }

    public function testImprovedPriceAlgorithmFallsBackToAuto(): void
    {
        $algorithm = $this->createMock(AlgorithmInterface::class);
        $algorithm->method('getItems')->willReturn([]);
        $this->algorithmRepository->expects(self::once())->method('get')->with('auto')->willReturn($algorithm);

        $this->adapter->query($this->request($this->categoryQuery(), [new DynamicBucket('price_bucket', 'price', 'improved')], [], 0, 12));
    }

    public function testBadRequestIsLoggedAndReportedAsEmptyRequest(): void
    {
        $this->failWith(400);
        $this->logger->expects(self::once())->method('critical');
        $this->expectException(EmptyRequestDataException::class);

        $this->adapter->query($this->request($this->categoryQuery(), [], [], 0, 10));
    }

    public function testServerErrorIsLoggedAndRaised(): void
    {
        $this->failWith(503);
        $this->logger->expects(self::once())->method('critical');
        $this->expectException(class_exists(ClientException::class) ? ClientException::class : ApiException::class);

        $this->adapter->query($this->request($this->categoryQuery(), [], [], 0, 10));
    }

    /**
     * @param int $status
     * @return void
     */
    private function failWith(int $status): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getReasonPhrase')->willReturn('error');
        $this->post = fn () => throw new ApiException($response, ['message' => 'error']);
    }

    /**
     * @return QueryInterface
     */
    private function categoryQuery(): QueryInterface
    {
        return new FilterQuery('category', 1, FilterQuery::REFERENCE_FILTER, new TermFilter('category_filter', 4, 'category_ids'));
    }

    /**
     * @param QueryInterface $query
     * @param array $buckets
     * @param array $sort
     * @param int $from
     * @param int $size
     * @return Request
     */
    private function request(QueryInterface $query, array $buckets, array $sort, int $from, int $size): Request
    {
        return new Request('catalog_view_container', 'catalogsearch_fulltext', $query, $from, $size, [
            'scope' => new Dimension('scope', 1),
        ], $buckets, $sort);
    }
}
