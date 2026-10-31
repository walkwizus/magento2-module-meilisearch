<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Meilisearch\Contracts\HybridSearchOptions;
use Meilisearch\Exceptions\ApiException;
use Psr\Log\LoggerInterface;
use Meilisearch\Contracts\MultiSearchQueryFactory;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider\SearchConfigProvider;

class Ssr implements ArgumentInterface
{
    /**
     * @var array
     */
    private array $config;

    /**
     * @var array|null
     */
    private ?array $searchResultCache = null;

    /**
     * @param RequestInterface $request
     * @param MultiSearchQueryFactory $multiSearchQueryFactory
     * @param HybridSearchOptions $hybridSearchOptions
     * @param SearchManager $searchManager
     * @param ConfigProvider $configProvider
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoggerInterface $logger
     * @param ImageUrlBuilder $imageUrlBuilder
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly MultiSearchQueryFactory $multiSearchQueryFactory,
        private readonly HybridSearchOptions $hybridSearchOptions,
        private readonly SearchManager $searchManager,
        private readonly ConfigProvider $configProvider,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly LoggerInterface $logger,
        private readonly ImageUrlBuilder $imageUrlBuilder
    ) {
        $this->config = $this->configProvider->get();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getSearchResult(): array
    {
        if ($this->searchResultCache !== null) {
            return $this->searchResultCache;
        }

        $query = $this->getStringParam('q');
        $currentPageParam = (int)$this->request->getParam('page', 1);
        $currentPage = $currentPageParam > 0 ? $currentPageParam : 1;

        $defaultSortBy = (string)($this->config['defaultSortBy'] ?? '');
        $sortBy = $this->getSortBy();
        $sortDirection = $this->isDescending() ? 'desc' : 'asc';
        $indexName = $this->config['indexName'];
        $facets = $this->config['facets']['facetList'] ?? [];

        $hitsPerPage = $this->getHitsPerPage();
        $selectedFacets = $this->getSelectedFacets();
        $activeCodes = array_keys($selectedFacets);

        $hybridConfig = $this->config['hybridSearch'] ?? [];
        $hybridOptions = null;
        $threshold = null;

        if (!empty($query) && !empty($hybridConfig['enabled']) && !empty($hybridConfig['embedder'])) {
            $hybridOptions = $this->hybridSearchOptions
                ->setSemanticRatio((float)$hybridConfig['semanticRatio'])
                ->setEmbedder((string)$hybridConfig['embedder']);

            if (isset($hybridConfig['rankingScoreThreshold'])) {
                $threshold = (float)$hybridConfig['rankingScoreThreshold'];
            }
        }

        $sort = $this->buildSort($sortBy, $sortDirection, $defaultSortBy);
        $queries = $this->buildQueries($indexName, $query, $facets, $selectedFacets, $currentPage, $hitsPerPage, $sort, $hybridOptions, $threshold);

        try {
            $results = $this->searchManager->multisearch($queries);
        } catch (ApiException $e) {
            if ($hybridOptions === null) {
                throw $e;
            }

            $this->logger->warning('Meilisearch: hybrid search failed, falling back to keyword search', ['exception' => $e]);
            $queries = $this->buildQueries($indexName, $query, $facets, $selectedFacets, $currentPage, $hitsPerPage, $sort, null, null);
            $results = $this->searchManager->multisearch($queries);
        }

        $lastPage = (int)($results['results'][0]['totalPages'] ?? 0);
        if ($currentPage > $lastPage && $lastPage > 0) {
            // past the last page, show the last page, as core's product collection does
            foreach ($queries as $pageQuery) {
                $pageQuery->setPage($lastPage);
            }
            $results = $this->searchManager->multisearch($queries);
        }

        $this->searchResultCache = $this->mergeDisjunctiveResults($results, $activeCodes);

        return $this->searchResultCache;
    }

    /**
     * @param string $indexName
     * @param string $query
     * @param array $facets
     * @param array $selectedFacets
     * @param int $currentPage
     * @param int $hitsPerPage
     * @param string|null $sort
     * @param HybridSearchOptions|null $hybridOptions
     * @param float|null $threshold
     * @return array
     */
    private function buildQueries(
        string $indexName,
        string $query,
        array $facets,
        array $selectedFacets,
        int $currentPage,
        int $hitsPerPage,
        ?string $sort,
        ?HybridSearchOptions $hybridOptions,
        ?float $threshold
    ): array {
        $queries = [];
        $mainFilters = $this->buildFilters($selectedFacets);

        $mainQuery = $this->multiSearchQueryFactory->create()
            ->setIndexUid($indexName)
            ->setQuery($query)
            ->setFacets($facets)
            ->setPage($currentPage)
            ->setHitsPerPage($hitsPerPage);

        if ($hybridOptions) {
            $mainQuery->setHybrid($hybridOptions);
            if ($threshold !== null) {
                $mainQuery->setRankingScoreThreshold($threshold);
            }
        }

        if (!empty($mainFilters)) {
            $mainQuery->setFilter($mainFilters);
        }

        if ($sort !== null) {
            $mainQuery->setSort([$sort]);
        }

        $queries[] = $mainQuery;

        foreach (array_keys($selectedFacets) as $code) {
            $excludeFilters = $selectedFacets;
            unset($excludeFilters[$code]);

            $filters = $this->buildFilters($excludeFilters);

            $disjunctiveQuery = $this->multiSearchQueryFactory->create()
                ->setIndexUid($indexName)
                ->setQuery($query)
                ->setFacets([$code])
                ->setPage($currentPage)
                ->setHitsPerPage($hitsPerPage);

            if ($hybridOptions) {
                $disjunctiveQuery->setHybrid($hybridOptions);
                if ($threshold !== null) {
                    $disjunctiveQuery->setRankingScoreThreshold($threshold);
                }
            }

            if (!empty($filters)) {
                $disjunctiveQuery->setFilter($filters);
            }

            $queries[] = $disjunctiveQuery;
        }

        return $queries;
    }

    /**
     * @param string $sortBy
     * @param string $sortDirection
     * @param string $defaultSortBy
     * @return string|null
     */
    private function buildSort(string $sortBy, string $sortDirection, string $defaultSortBy): ?string
    {
        if ($sortBy === SearchConfigProvider::SORT_RELEVANCE) {
            return null;
        }

        if ($sortBy !== '') {
            return $sortBy . ':' . $sortDirection;
        }

        if ($defaultSortBy !== '' && $defaultSortBy !== SearchConfigProvider::SORT_RELEVANCE) {
            return $defaultSortBy . ':asc';
        }

        return null;
    }

    /**
     * @param array $multiResults
     * @param array $activeCodes
     * @return array
     */
    private function mergeDisjunctiveResults(array $multiResults, array $activeCodes): array
    {
        if (!isset($multiResults['results'][0]) || !is_array($multiResults['results'][0])) {
            return [
                'hits' => [],
                'facetDistribution' => [],
                'totalHits' => 0,
                'totalPages' => 0,
            ];
        }

        $mainResults = $multiResults['results'][0];
        $finalDistribution = $mainResults['facetDistribution'] ?? [];

        foreach ($activeCodes as $index => $code) {
            $disjunctiveIndex = $index + 1;

            if (isset($multiResults['results'][$disjunctiveIndex]['facetDistribution'][$code])) {
                $finalDistribution[$code] = $multiResults['results'][$disjunctiveIndex]['facetDistribution'][$code];
            }
        }

        $mainResults['facetDistribution'] = $finalDistribution;

        return $mainResults;
    }

    /**
     * @param array|null $selectedFacets
     * @return array
     */
    public function buildFilters(?array $selectedFacets = null): array
    {
        $filters = [];

        $categoryId = (int)($this->config['currentCategoryId'] ?? 0);
        if ($categoryId > 0) {
            $filters[] = 'category_ids = ' . $categoryId;
        } else {
            $categoryRule = (string)($this->config['categoryRule'] ?? '');
            if ($categoryRule !== '') {
                $filters[] = $categoryRule;
            }
        }

        $selectedFacets = $selectedFacets ?? $this->getSelectedFacets();

        foreach ($selectedFacets as $name => $values) {
            $orGroup = [];
            foreach ($values as $value) {
                $orGroup[] = $this->buildFacetExpression($name, (string)$value);
            }

            if ($orGroup) {
                $filters[] = $orGroup;
            }
        }

        return $filters;
    }

    /**
     * @param string $facetName
     * @param string $value
     * @return string
     */
    private function buildFacetExpression(string $facetName, string $value): string
    {
        if ($this->isRangeFacet($facetName)) {
            $range = $this->extractRangeBounds($value);
            if ($range !== null) {
                [$from, $to] = $range;
                return sprintf('(%1$s >= %2$s AND %1$s <= %3$s)', $facetName, $from, $to);
            }
        }

        $escapedValue = str_replace('"', '\"', $value);
        return sprintf('%s = "%s"', $facetName, $escapedValue);
    }

    /**
     * @param string $facetName
     * @return bool
     */
    private function isRangeFacet(string $facetName): bool
    {
        $facetConfig = (array)($this->config['facets']['facetConfig'] ?? []);
        $config = (array)($facetConfig[$facetName] ?? []);

        if (($config['type'] ?? null) === 'price' || ($config['renderRegion'] ?? null) === 'price') {
            return true;
        }

        return preg_match('/^price(?:_|$)/', $facetName) === 1;
    }

    /**
     * @param string $value
     * @return array|null
     */
    private function extractRangeBounds(string $value): ?array
    {
        if (
            preg_match(
                '/^\s*(-?\d+(?:\.\d+)?)\s*(?:_|-)\s*(-?\d+(?:\.\d+)?)\s*$/',
                $value,
                $matches
            ) !== 1
        ) {
            return null;
        }

        $from = $matches[1];
        $to = $matches[2];

        if ((float)$from > (float)$to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array
     */
    public function getSelectedFacets(): array
    {
        $selected = [];
        $facetList = $this->config['facets']['facetList'] ?? [];
        $params = $this->request->getParams();

        foreach ($params as $name => $value) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            $resolvedName = $this->resolveFacetParameterName($name, $facetList);
            if ($resolvedName === null) {
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            $labels = array_filter(array_map('trim', explode(',', $value)));

            if (!empty($labels)) {
                $selected[$resolvedName] = array_values(
                    array_unique(
                        array_merge($selected[$resolvedName] ?? [], $labels)
                    )
                );
            }
        }

        return $selected;
    }

    /**
     * @param string $name
     * @param array $facetList
     * @return string|null
     */
    private function resolveFacetParameterName(string $name, array $facetList): ?string
    {
        if (in_array($name, $facetList, true)) {
            return $name;
        }

        if (preg_match('/^price_\d+(?:_\d+)*$/', $name) !== 1) {
            return null;
        }

        $priceFacetCodes = array_values(array_filter(
            $facetList,
            static fn ($facetCode): bool => is_string($facetCode) && preg_match('/^price_\d+(?:_\d+)*$/', $facetCode) === 1
        ));

        foreach ($priceFacetCodes as $facetCode) {
            if (
                $facetCode === $name
                || str_starts_with($name, $facetCode . '_')
                || str_starts_with($facetCode, $name . '_')
            ) {
                return $facetCode;
            }
        }

        return count($priceFacetCodes) === 1 ? $priceFacetCodes[0] : null;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getFacets(): array
    {
        $searchResult = $this->getSearchResult();
        $facetDistribution = (array)($searchResult['facetDistribution'] ?? []);
        $facetConfig = (array)($this->config['facets']['facetConfig'] ?? []);

        $facets = array_filter(
            $facetConfig,
            static fn (array $cfg, string $code): bool => isset($facetDistribution[$code]),
            ARRAY_FILTER_USE_BOTH
        );

        uasort(
            $facets,
            static fn (array $a, array $b): int => (int)($a['position'] ?? 0) <=> (int)($b['position'] ?? 0)
        );

        return array_values($facets);
    }

    /**
     * @return int
     */
    public function getHitsPerPage(): int
    {
        $mode = $this->getProductListMode();
        $limit = $this->getStringParam('product_list_limit');
        $limit = ctype_digit($limit) ? (int)$limit : 0;

        // only the configured limits, as core's toolbar does
        return in_array($limit, $this->config[$mode . 'PerPageValues'] ?? [], true)
            ? $limit
            : (int)$this->config[$mode . 'PerPage'];
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getProductListMode(): string
    {
        $mode = $this->getStringParam('product_list_mode');
        $modes = array_keys($this->config['availableViewMode'] ?? ['grid' => '', 'list' => '']);

        return in_array($mode, $modes, true) ? $mode : ($this->config['defaultViewMode'] ?? 'grid');
    }

    /**
     * @return string
     */
    public function getSortBy(): string
    {
        $available = $this->config['availableSortBy'] ?? [];

        $sort = $this->getStringParam('product_list_order');
        if ($sort && isset($available[$sort])) {
            return $sort;
        }

        $default = (string)($this->config['defaultSortBy'] ?? '');
        if ($default && isset($available[$default])) {
            return $default;
        }

        return (string)array_key_first($available);
    }

    /**
     * @return bool
     */
    public function isDescending(): bool
    {
        $dir = strtolower($this->getStringParam('product_list_dir'));
        return $dir === 'desc';
    }

    /**
     * @param string $urlKey
     * @return string
     * @throws LocalizedException
     */
    public function getProductUrl(string $urlKey): string
    {
        $baseUrl = rtrim((string)($this->config['baseUrl'] ?? ''), '/');
        $path = ltrim(trim($urlKey), '/');

        if ($path === '') {
            throw new LocalizedException(__("Unable to find path for product."));
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $baseUrl . '/' . $path;
    }

    /**
     * @param string $image
     * @return string
     */
    public function getProductImage(string $image): string
    {
        $mode = $this->getProductListMode();

        // The view mode is an unvalidated request parameter. An unknown value resolves to an
        // image display area that has no view.xml entry, which silently renders the placeholder
        // on every tile instead of the product image.
        if (!in_array($mode, ['grid', 'list'], true)) {
            $mode = 'grid';
        }

        return $this->imageUrlBuilder->getUrl($image, 'category_page_' . $mode);
    }

    /**
     * @param float|string $price
     * @return string
     */
    public function getProductPrice($price): string
    {
        return $this->priceCurrency->convertAndFormat((float)$price);
    }

    /**
     * Query params arrive as arrays when written as name[]=, so anything non-scalar is ignored.
     *
     * @param string $name
     * @return string
     */
    private function getStringParam(string $name): string
    {
        $value = $this->request->getParam($name);
        return is_scalar($value) ? (string)$value : '';
    }
}
