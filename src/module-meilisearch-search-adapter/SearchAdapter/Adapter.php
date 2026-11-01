<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\SearchAdapter;

use Magento\AdvancedSearch\Model\Client\ClientException;
use Magento\Catalog\Model\Layer\Filter\Price\Range as PriceRange;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\Dynamic\Algorithm\Repository as AlgorithmRepository;
use Magento\Framework\Search\Dynamic\EntityStorageFactory;
use Magento\Framework\Search\Request\Aggregation\DynamicBucket;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\Filter\BoolExpression as BoolFilter;
use Magento\Framework\Search\Request\Filter\Range as RangeFilter;
use Magento\Framework\Search\Request\Filter\Term as TermFilter;
use Magento\Framework\Search\Request\Filter\Wildcard as WildcardFilter;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\Query\BoolExpression as BoolQuery;
use Magento\Framework\Search\Request\Query\Filter as FilterQuery;
use Magento\Framework\Search\Request\Query\MatchQuery;
use Magento\Framework\Search\Request\QueryInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Meilisearch\Contracts\MultiSearchQuery;
use Meilisearch\Exceptions\ApiException;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchSearchAdapter\Model\AttributeProvider\CatalogProduct\OptionIds;
use Walkwizus\MeilisearchSearchAdapter\Model\Config;
use Walkwizus\MeilisearchSearchAdapter\Model\IndexSettings;
use Walkwizus\MeilisearchSearchAdapter\SearchAdapter\Dynamic\PriceDataProvider;

class Adapter implements AdapterInterface
{
    private const PLAIN_FIELDS = ['category_ids', 'id', 'sku', 'name'];

    /**
     * Meilisearch has no false literal.
     */
    private const NO_MATCH = 'id IN []';

    /**
     * Filter cost grows faster than the id list (10,000 ids: 0.6 s).
     */
    private const MAX_IDS = 10000;

    /**
     * Meilisearch ignores query words past the tenth.
     */
    private const MAX_WORDS = 10;

    private string $indexName = '';
    private int $storeId = 0;

    /**
     * @var string[] search text from match queries on every field
     */
    private array $text = [];

    /**
     * @var array[] text conditions on named fields, as [fields, queries], resolved to ids in one multi-search
     */
    private array $lookups = [];

    /**
     * @param ConnectionManager $connectionManager
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param ScopeResolverInterface $scopeResolver
     * @param ResponseFactory $responseFactory
     * @param AlgorithmRepository $algorithmRepository
     * @param EntityStorageFactory $entityStorageFactory
     * @param PriceRange $priceRange
     * @param EavConfig $eavConfig
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     * @param IndexSettings $indexSettings
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Config $config
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly ScopeResolverInterface $scopeResolver,
        private readonly ResponseFactory $responseFactory,
        private readonly AlgorithmRepository $algorithmRepository,
        private readonly EntityStorageFactory $entityStorageFactory,
        private readonly PriceRange $priceRange,
        private readonly EavConfig $eavConfig,
        private readonly CustomerSession $customerSession,
        private readonly LoggerInterface $logger,
        private readonly IndexSettings $indexSettings,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly Config $config
    ) { }

    /**
     * @param RequestInterface $request
     * @return QueryResponse
     * @throws ClientException
     * @throws EmptyRequestDataException
     * @throws \Exception
     */
    public function query(RequestInterface $request): QueryResponse
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->scopeResolver->getScope(current($request->getDimensions())->getValue());
        $this->storeId = (int)$store->getId();
        $this->indexName = $this->searchIndexNameResolver->getIndexName($this->storeId, $request->getIndex());
        $this->text = [];
        $this->lookups = [];
        $size = (int)$request->getSize();

        try {
            $filter = $this->expression($request->getQuery());
            $text = $this->searchText();
            if ($text === null) {
                return $this->response($request, [], []);
            }
            $filter = $this->resolveLookups($filter);

            $params = array_filter([
                'filter' => $filter,
                'sort' => $this->sort($request, (int)$store->getRootCategoryId()),
                'facets' => $this->facets($request),
                'attributesToRetrieve' => ['id'],
                'showRankingScore' => true,
                'hitsPerPage' => $size,
                'page' => $size ? intdiv((int)$request->getFrom(), $size) + 1 : 1,
            ], fn ($value) => $value !== null && $value !== []);

            $result = $this->search($text, $params);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            if ($e instanceof ApiException && in_array($e->httpStatus, [400, 404], true)) {
                throw new EmptyRequestDataException('Could not perform search query.');
            }
            // ClientException is new in Magento 2.4.7
            throw class_exists(ClientException::class)
                ? new ClientException('Could not perform search query.', (int)$e->getCode(), $e)
                : $e;
        }

        return $this->response($request, $result['hits'] ?? [], $result, $result['totalHits'] ?? 0);
    }

    /**
     * Relaxes the search only while nothing matches: exact words, typos, known words only, fallback strategy.
     *
     * @param string $text
     * @param array $params
     * @return array
     */
    private function search(string $text, array $params): array
    {
        $strategy = $this->config->getMatchingStrategy($this->storeId);
        $result = $this->searchWords($text, $strategy, $params);
        if ($text === '' || ($result['totalHits'] ?? 0) > 0 || str_contains($text, '"')) {
            return $result;
        }

        $known = $this->knownWords($text, $params['filter'] ?? null);
        if ($known !== '' && $known !== $text) {
            $result = $this->searchWords($known, $strategy, $params);
            if (($result['totalHits'] ?? 0) > 0) {
                return $result;
            }
        }

        $fallback = $this->config->getFallbackStrategy($this->storeId);

        return $fallback !== null
            ? $this->rawSearch($known ?: $text, $fallback, $params)
            : $result;
    }

    /**
     * @param string $text
     * @param string $strategy
     * @param array $params
     * @return array
     */
    private function searchWords(string $text, string $strategy, array $params): array
    {
        if ($text !== '' && !str_contains($text, '"') && $this->config->isExactWordsFirst($this->storeId)) {
            // a quoted word matches without typos (or as a prefix)
            $result = $this->rawSearch('"' . implode('" "', explode(' ', $text)) . '"', $strategy, $params);
            if (($result['totalHits'] ?? 0) > 0) {
                return $result;
            }
        }

        return $this->rawSearch($text, $strategy, $params);
    }

    /**
     * @param string $text
     * @param string|null $filter
     * @return string the words of the text that match at least one product
     */
    private function knownWords(string $text, ?string $filter): string
    {
        $words = array_slice(explode(' ', $text), 0, self::MAX_WORDS);
        $results = $this->connectionManager->getConnection()->multiSearch(array_map(
            function ($word) use ($filter) {
                $query = (new MultiSearchQuery())->setIndexUid($this->indexName)->setQuery($word)->setLimit(0);

                return $filter !== null ? $query->setFilter($filter) : $query;
            },
            $words
        ))['results'] ?? [];

        return implode(' ', array_filter($words, fn ($i) => ($results[$i]['estimatedTotalHits'] ?? 0) > 0, ARRAY_FILTER_USE_KEY));
    }

    /**
     * @param string $text
     * @param string $strategy
     * @param array $params
     * @return array
     */
    private function rawSearch(string $text, string $strategy, array $params): array
    {
        return $this->connectionManager->getConnection()->index($this->indexName)
            ->rawSearch($text, $params + ['matchingStrategy' => $strategy]);
    }

    /**
     * @param RequestInterface $request
     * @param array $hits
     * @param array $result
     * @param int $total
     * @return QueryResponse
     */
    private function response(RequestInterface $request, array $hits, array $result, int $total = 0): QueryResponse
    {
        $documents = array_map(
            fn ($hit) => ['_id' => $hit['id'], '_score' => $hit['_rankingScore'] ?? 0],
            $hits
        );

        return $this->responseFactory->create([
            'documents' => $documents,
            'aggregations' => $this->aggregations($request, $result, array_column($documents, '_id')),
            'total' => $total,
        ]);
    }

    /**
     * Core binds the search term to several match queries.
     *
     * @return string|null null when the text has nothing to match on
     */
    private function searchText(): ?string
    {
        $text = trim(implode(' ', array_unique(array_map('trim', $this->text))));
        if ($text === '') {
            return '';
        }

        $clean = $this->cleanText($text);

        return $clean !== null && preg_match('/^"[^"]+"$/', $text) ? '"' . $clean . '"' : $clean;
    }

    /**
     * Strips Meilisearch's "phrase" and -negation syntax.
     *
     * @param string $text
     * @return string|null null when the text has nothing to match on
     */
    private function cleanText(string $text): ?string
    {
        $text = trim(preg_replace(['/"/', '/(^|\s)-+/u', '/\s+/u'], [' ', '$1', ' '], $text));

        return preg_match('/[\p{L}\p{N}]/u', $text) ? $text : null;
    }

    /**
     * Named-field text is a clause of its own in core, so it resolves to ids rather than joining the search text.
     *
     * @param string[] $fields
     * @param string $value
     * @param bool $allWords
     * @return string the condition, or a placeholder for its ids
     */
    private function fieldMatch(array $fields, string $value, bool $allWords): string
    {
        $fields = array_values(array_filter(
            array_unique($fields),
            fn ($field) => $this->indexSettings->isSearchable($this->indexName, $field)
        ));
        // split like core's analyser
        $words = preg_split(
            '/(?:[^\p{L}\p{M}\p{N}\'’_.]|(?<![\p{L}\p{M}\p{N}])[\'’_.]|[\'’_.](?![\p{L}\p{M}\p{N}]))++/u',
            $value,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];
        $words = array_intersect_key($words, array_unique(array_map('mb_strtolower', $words)));
        $words = array_slice(array_values($words), 0, self::MAX_WORDS);
        // a lone letter would prefix-match every word
        $last = $allWords ? array_key_last($words) : null;
        foreach ($words as $i => $word) {
            if ($i !== $last && preg_match('/^\X$/u', $word)) {
                $words[$i] = '"' . $word . '"';
            }
        }
        if (!$fields || !$words) {
            return self::NO_MATCH;
        }

        $this->lookups[] = [$fields, $allWords ? [implode(' ', $words)] : $words];

        // json_encode escapes NUL, so request input cannot forge a placeholder
        return "\0" . array_key_last($this->lookups) . "\0";
    }

    /**
     * @param string|null $filter
     * @return string|null
     */
    private function resolveLookups(?string $filter): ?string
    {
        if (!$this->lookups) {
            return $filter;
        }

        $queries = [];
        foreach ($this->lookups as $i => [$fields, $texts]) {
            foreach ($texts as $text) {
                $queries[] = [$i, $fields, $text];
            }
        }
        $limit = max(1, intdiv(self::MAX_IDS, count($queries)));
        $results = $this->connectionManager->getConnection()->multiSearch(array_map(
            fn ($query) => (new MultiSearchQuery())
                ->setIndexUid($this->indexName)
                ->setQuery($query[2])
                ->setAttributesToSearchOn($query[1])
                ->setMatchingStrategy('all')
                ->setAttributesToRetrieve(['id'])
                ->setLimit($limit),
            $queries
        ))['results'] ?? [];

        $ids = array_fill_keys(array_keys($this->lookups), []);
        $truncated = [];
        foreach ($results as $n => $result) {
            $hits = array_column($result['hits'] ?? [], 'id');
            if (($result['estimatedTotalHits'] ?? 0) > count($hits)) {
                $truncated[] = $queries[$n][2];
            }
            array_push($ids[$queries[$n][0]], ...$hits);
        }
        if ($truncated) {
            $this->logger->warning(sprintf('Meilisearch: "%s" match more than %d products each, the rest are left out.', implode('", "', $truncated), $limit));
        }

        return preg_replace_callback(
            '/\0(\d+)\0/',
            fn ($match) => 'id IN [' . implode(', ', array_unique(array_map('intval', $ids[$match[1]]))) . ']',
            $filter
        );
    }

    /**
     * @param RequestInterface $request
     * @return array
     */
    private function facets(RequestInterface $request): array
    {
        $fields = array_map(fn (BucketInterface $bucket) => $this->field($bucket->getField()), $request->getAggregation());

        return array_values(array_unique(array_filter(
            $fields,
            fn ($field) => $this->indexSettings->isFilterable($this->indexName, $field)
        )));
    }

    /**
     * @param QueryInterface $query
     * @return string|null
     */
    private function expression(QueryInterface $query): ?string
    {
        if ($query instanceof MatchQuery) {
            $fields = array_column($query->getMatches(), 'field');
            if (!in_array('*', $fields, true)) {
                $phrase = in_array('match_phrase_prefix', array_column($query->getMatches(), 'matchCondition'), true);
                return $this->fieldMatch($fields, (string)$query->getValue(), $phrase);
            }
            $this->text[] = (string)$query->getValue();
            return null;
        }
        if ($query instanceof FilterQuery) {
            return $query->getReferenceType() === FilterQuery::REFERENCE_FILTER
                ? $this->filter($query->getReference())
                : $this->expression($query->getReference());
        }
        if ($query instanceof BoolQuery) {
            return $this->bool($query->getMust(), $query->getShould(), $query->getMustNot(), [$this, 'expression']);
        }

        throw new \InvalidArgumentException('Unsupported query type ' . $query->getType());
    }

    /**
     * @param FilterInterface $filter
     * @return string|null
     */
    private function filter(FilterInterface $filter): ?string
    {
        if ($filter instanceof TermFilter) {
            return $this->term($filter->getField(), $filter->getValue());
        }
        if ($filter instanceof RangeFilter) {
            $field = $this->field($filter->getField());
            $parts = [];
            if ($filter->getFrom() !== null && $filter->getFrom() !== '') {
                $parts[] = $field . ' >= ' . $this->literal($filter->getFrom());
            }
            if ($filter->getTo() !== null && $filter->getTo() !== '') {
                $parts[] = $field . ' <= ' . $this->literal($filter->getTo());
            }
            if (!$parts) {
                return null;
            }
            return $this->indexSettings->isFilterable($this->indexName, $field) ? implode(' AND ', $parts) : self::NO_MATCH;
        }
        if ($filter instanceof WildcardFilter) {
            // Meilisearch's CONTAINS operator is experimental
            return $this->fieldMatch([$filter->getField()], (string)$filter->getValue(), true);
        }
        if ($filter instanceof BoolFilter) {
            return $this->bool($filter->getMust(), $filter->getShould(), $filter->getMustNot(), [$this, 'filter']);
        }

        throw new \InvalidArgumentException('Unsupported filter type ' . $filter->getType());
    }

    /**
     * @param string $code
     * @param mixed $value a value, a list, or one ['eq' | 'in' | 'neq' | 'nin' => value] condition
     * @return string|null
     */
    private function term(string $code, $value): ?string
    {
        $negate = false;
        if (is_array($value) && in_array(array_key_first($value), ['eq', 'in', 'neq', 'nin'], true)) {
            $negate = in_array(array_key_first($value), ['neq', 'nin'], true);
            $value = reset($value);
        }
        if ($value === false || $value === null || $value === '' || $value === []) {
            return null;
        }

        if ($code === 'url_key') {
            // the index holds the product's URL path, not its url_key attribute
            $expression = $this->urlKeyFilter((array)$value);
        } else {
            $field = $this->field($code);
            if (!$this->indexSettings->isFilterable($this->indexName, $field)) {
                return $negate ? null : self::NO_MATCH;
            }
            $expression = is_array($value)
                ? $field . ' IN [' . implode(', ', array_map([$this, 'literal'], $value)) . ']'
                : $field . ' = ' . $this->literal($value);
        }

        return $negate ? 'NOT (' . $expression . ')' : $expression;
    }

    /**
     * @param array $urlKeys
     * @return string
     */
    private function urlKeyFilter(array $urlKeys): string
    {
        $ids = $this->productCollectionFactory->create()
            ->setStoreId($this->storeId)
            ->addAttributeToFilter('url_key', ['in' => $urlKeys])
            ->getAllIds();

        return 'id IN [' . implode(', ', array_map('intval', $ids)) . ']';
    }

    /**
     * @param array $must
     * @param array $should
     * @param array $mustNot
     * @param callable $build
     * @return string|null
     */
    private function bool(array $must, array $should, array $mustNot, callable $build): ?string
    {
        $parts = [];
        foreach ($must as $item) {
            $parts[] = $build($item);
        }

        $any = [];
        foreach ($should as $item) {
            $any[] = $build($item);
        }
        if ($any = array_filter($any)) {
            $parts[] = '(' . implode(') OR (', $any) . ')';
        }

        foreach ($mustNot as $item) {
            $not = $build($item);
            $parts[] = $not === null ? null : 'NOT (' . $not . ')';
        }

        $parts = array_filter($parts);

        return $parts ? '(' . implode(') AND (', $parts) . ')' : null;
    }

    /**
     * @param RequestInterface $request
     * @param int $rootCategoryId
     * @return array
     */
    private function sort(RequestInterface $request, int $rootCategoryId): array
    {
        $sort = [];
        $items = method_exists($request, 'getSort') ? $request->getSort() : [];
        foreach ($items as $item) {
            if (in_array($item['field'], ['relevance', '_score'], true)) {
                // later sorts (core's entity_id tiebreak) would override relevance
                break;
            }
            $direction = strtolower($item['direction'] ?? '') === 'desc' ? 'desc' : 'asc';
            if ($item['field'] === 'is_out_of_stock') {
                // the index holds the inverse flag
                $direction = $direction === 'asc' ? 'desc' : 'asc';
            }
            $field = match ($item['field']) {
                'position' => 'position_category_' . ($this->categoryId($request) ?? $rootCategoryId),
                'entity_id' => 'id',
                'is_out_of_stock' => 'stock.is_in_stock',
                // option attributes sort on their label, as in core
                default => $item['field'] === 'price' ? $this->field('price') : $item['field'],
            };
            if ($this->indexSettings->isSortable($this->indexName, $field)) {
                $sort[] = $field . ':' . $direction;
            }
        }

        return $sort;
    }

    /**
     * @param RequestInterface $request
     * @param array $result
     * @param array $ids
     * @return array
     */
    private function aggregations(RequestInterface $request, array $result, array $ids): array
    {
        $aggregations = [];
        foreach ($request->getAggregation() as $bucket) {
            $values = $result['facetDistribution'][$this->field($bucket->getField())] ?? [];
            $aggregations[$bucket->getName()] = [];

            if (!$values) {
                continue;
            }
            if (!$bucket instanceof DynamicBucket) {
                foreach ($values as $value => $count) {
                    $aggregations[$bucket->getName()][$value] = ['value' => $value, 'count' => $count];
                }
                continue;
            }

            $algorithm = $this->algorithmRepository->get(
                // improved needs interval queries a facet cannot answer
                $bucket->getMethod() === 'improved' ? 'auto' : $bucket->getMethod(),
                ['dataProvider' => new PriceDataProvider($this->priceRange, $values)]
            );
            $ranges = $algorithm->getItems($bucket, $request->getDimensions(), $this->entityStorageFactory->create($ids));
            foreach ($ranges as $range) {
                $name = $range['from'] . '_' . $range['to'];
                $aggregations[$bucket->getName()][$name] = ['value' => $name] + $range;
            }
        }

        return $aggregations;
    }

    /**
     * @param string $code
     * @return string
     */
    private function field(string $code): string
    {
        if ($code === 'price') {
            return 'price_' . (int)$this->customerSession->getCustomerGroupId();
        }
        if (in_array($code, self::PLAIN_FIELDS, true)) {
            return $code;
        }

        return $this->eavConfig->getAttribute(Product::ENTITY, $code)->usesSource() ? $code . OptionIds::SUFFIX : $code;
    }

    /**
     * @param RequestInterface $request
     * @return int|null
     */
    private function categoryId(RequestInterface $request): ?int
    {
        $query = $request->getQuery();
        $category = $query instanceof BoolQuery ? ($query->getMust()['category'] ?? null) : null;
        $reference = $category instanceof FilterQuery ? $category->getReference() : null;
        $value = $reference instanceof TermFilter ? $reference->getValue() : null;
        if (is_array($value)) {
            $value = $value['in'] ?? $value['eq'] ?? $value;
            $value = is_array($value) ? reset($value) : $value;
        }

        return $value === null || $value === false ? null : (int)$value;
    }

    /**
     * Always quoted: Meilisearch rejects some numbers PHP accepts ("+1") and compares quoted ones numerically.
     *
     * @param mixed $value
     * @return string
     */
    private function literal($value): string
    {
        return json_encode(
            (string)$value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
