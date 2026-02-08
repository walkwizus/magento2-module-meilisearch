<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Meilisearch\Contracts\SearchQueryFactory;
use Meilisearch\Contracts\HybridSearchOptions; // Nouvel import
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
     * @param SearchManager $searchManager
     * @param ConfigProvider $configProvider
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SearchQueryFactory $searchQueryFactory,
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

        if (!empty($query) && !empty($hybridConfig['enabled']) && !empty($hybridConfig['embedder'])) {
            $hybridOptions = (new HybridSearchOptions())
                ->setSemanticRatio((float)$hybridConfig['semanticRatio'])
                ->setEmbedder((string)$hybridConfig['embedder']);
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
                $escapedValue = str_replace('"', '\"', (string)$value);
                $orGroup[] = sprintf('%s = "%s"', $name, $escapedValue);
            }

            if ($orGroup) {
                $filters[] = $orGroup;
            }
        }

        return $filters;
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
            if (!in_array($name, $facetList, true)) {
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            $labels = array_filter(array_map('trim', explode(',', $value)));

            if (!empty($labels)) {
                $selected[$name] = array_values($labels);
            }
        }

        return $selected;
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
        $defaultViewMode = $this->config['defaultViewMode'];
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
        return $this->config['baseUrl'] . $urlKey . $this->config['productUrlSuffix'];
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
