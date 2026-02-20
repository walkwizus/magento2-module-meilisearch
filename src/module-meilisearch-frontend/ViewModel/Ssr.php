<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Meilisearch\Contracts\SearchQueryFactory;
use Meilisearch\Contracts\HybridSearchOptions;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Magento\Framework\Pricing\PriceCurrencyInterface;

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
     * @param SearchQueryFactory $searchQueryFactory
     * @param HybridSearchOptions $hybridSearchOptions
     * @param SearchManager $searchManager
     * @param ConfigProvider $configProvider
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SearchQueryFactory $searchQueryFactory,
        private readonly HybridSearchOptions $hybridSearchOptions,
        private readonly SearchManager $searchManager,
        private readonly ConfigProvider $configProvider,
        private readonly PriceCurrencyInterface $priceCurrency
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

        $query = $this->request->getParam('q', '');
        $currentPageParam = (int)$this->request->getParam('page', 1);
        $currentPage = $currentPageParam > 0 ? $currentPageParam : 1;

        $defaultSortBy = $this->config['defaultSortBy'] ?? null;
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

        $queries = [];
        $mainFilters = $this->buildFilters($selectedFacets);

        $mainQuery = $this->searchQueryFactory->create()
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

        if (!empty($defaultSortBy)) {
            $mainQuery->setSort([$defaultSortBy . ':asc']);
        }

        $queries[] = $mainQuery;

        foreach ($activeCodes as $code) {
            $excludeFilters = $selectedFacets;
            unset($excludeFilters[$code]);

            $filters = $this->buildFilters($excludeFilters);

            $disjunctiveQuery = $this->searchQueryFactory->create()
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

        $results = $this->searchManager->multisearch($queries);
        $this->searchResultCache = $this->mergeDisjunctiveResults($results, $activeCodes);

        return $this->searchResultCache;
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
     * Accepts both "from_to" and "from-to" numeric formats.
     *
     * @param string $value
     * @return array{0: string, 1: string}|null
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
            static fn($facetCode): bool => is_string($facetCode) && preg_match('/^price_\d+(?:_\d+)*$/', $facetCode) === 1
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
            static fn(array $cfg, string $code): bool => isset($facetDistribution[$code]),
            ARRAY_FILTER_USE_BOTH
        );

        uasort(
            $facets,
            static fn(array $a, array $b): int => (int)($a['position'] ?? 0) <=> (int)($b['position'] ?? 0)
        );

        return array_values($facets);
    }

    /**
     * @return int
     */
    public function getHitsPerPage(): int
    {
        $currentHitsPerPage = $this->request->getParam('product_list_limit');

        if ($currentHitsPerPage) {
            return (int)$currentHitsPerPage;
        }

        $defaultViewMode = $this->config['defaultViewMode'];
        $currentViewMode = $this->request->getParam('product_list_mode', $defaultViewMode);

        return (int)$this->config[$currentViewMode . 'PerPage'];
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
        $defaultViewMode = $this->config['defaultViewMode'] ?? 'grid';
        return (string)$this->request->getParam('product_list_mode', $defaultViewMode);
    }

    /**
     * @return string
     */
    public function getSortBy(): string
    {
        $available = $this->config['availableSortBy'] ?? [];

        $sort = (string)$this->request->getParam('product_list_order', '');
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
        $dir = strtolower((string) $this->request->getParam('product_list_dir', 'asc'));
        return $dir === 'desc';
    }

    /**
     * @param string $urlKey
     * @return string
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
        return $this->config['mediaBaseUrl'] . $image;
    }

    /**
     * @param float|string $price
     * @return string
     */
    public function getProductPrice($price): string
    {
        return $this->priceCurrency->convertAndFormat((float)$price);
    }
}
