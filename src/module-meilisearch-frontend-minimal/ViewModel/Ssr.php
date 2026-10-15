<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Meilisearch\Contracts\HybridSearchOptions;
use Meilisearch\Contracts\SearchQueryFactory;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

class Ssr extends \Walkwizus\MeilisearchFrontend\ViewModel\Ssr
{
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
        ConfigProvider $configProvider,
        PriceCurrencyInterface $priceCurrency
    ) {
        parent::__construct(
            $request,
            $searchQueryFactory,
            $hybridSearchOptions,
            $searchManager,
            $configProvider,
            $priceCurrency
        );
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

        $config = $this->getConfig();
        $defaultSortBy = (string)($config['defaultSortBy'] ?? '');
        $sortBy = $this->getSortBy();
        $sortDirection = $this->isDescending() ? 'desc' : 'asc';
        $indexName = (string)$config['indexName'];
        $facets = $config['facets']['facetList'] ?? [];

        $hitsPerPage = $this->getHitsPerPage();
        $selectedFacets = $this->getSelectedFacets();
        $activeCodes = array_keys($selectedFacets);

        $hybridConfig = $config['hybridSearch'] ?? [];
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

        if ($sortBy !== '') {
            $mainQuery->setSort([$sortBy . ':' . $sortDirection]);
        } elseif ($defaultSortBy !== '') {
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
}
