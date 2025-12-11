<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Meilisearch\Contracts\SearchQueryFactory;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSearchResult(): array
    {
        $query = $this->request->getParam('q', '');
        $currentPageParam = (int)$this->request->getParam('page', 1);
        $currentPage = $currentPageParam > 0 ? $currentPageParam : 1;

        $defaultSortBy = $this->config['defaultSortBy'];
        $indexName = $this->config['indexName'];
        $facets = $this->config['facets']['facetList'];

        $hitsPerPage = $this->getHitsPerPage();
        $selectedFacets = $this->getSelectedFacets();
        $activeCodes = array_keys($selectedFacets);

        $queries = [];

        $mainFilters = $this->buildFilters($selectedFacets);

        $mainQuery = $this->searchQueryFactory->create()
            ->setIndexUid($indexName)
            ->setQuery($query)
            ->setFacets($facets)
            ->setPage($currentPage)
            ->setHitsPerPage($hitsPerPage);

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

            $disjunctiveFilters = $this->searchQueryFactory->create()
                ->setIndexUid($indexName)
                ->setQuery($query)
                ->setFacets([$code])
                ->setPage($currentPage)
                ->setHitsPerPage($hitsPerPage);

            if (!empty($filters)) {
                $disjunctiveFilters->setFilter($filters);
            }

            $queries[] = $disjunctiveFilters;
        }

        $results = $this->searchManager->multisearch($queries);

        return $this->mergeDisjunctiveResults($results, $activeCodes);
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

            if (empty($multiResults['results'][$disjunctiveIndex]['facetDistribution'][$code])) {
                continue;
            }

            $facetValues = $multiResults['results'][$disjunctiveIndex]['facetDistribution'][$code];
            $finalDistribution[$code] = $facetValues;
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
            $filters[] = ['category_ids = ' . $categoryId];
        }

        $selectedFacets = $selectedFacets ?? $this->getSelectedFacets();

        foreach ($selectedFacets as $name => $values) {
            $orGroup = [];

            foreach ($values as $valueId) {
                $orGroup[] = sprintf('%s = %s', $name, $valueId);
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
        $facetList = array_map('strval', $facetList);

        $facetConfig = $this->config['facets']['facetConfig'] ?? [];
        $params = $this->request->getParams();

        foreach ($params as $name => $value) {
            if (!in_array($name, $facetList, true)) {
                continue;
            }

            if (!is_string($value) || $value === '') {
                continue;
            }

            $labels = array_filter(array_map('trim', explode(',', $value)));
            if (!$labels) {
                continue;
            }

            if (!isset($facetConfig[$name]['options'])) {
                continue;
            }

            $options = $facetConfig[$name]['options'];
            $values = [];

            foreach ($labels as $label) {
                $matchedValue = null;

                foreach ($options as $optValue => $optData) {
                    if (isset($optData['label']) && strcasecmp($optData['label'], $label) === 0) {
                        $matchedValue = (string)$optValue;
                        break;
                    }
                }

                if ($matchedValue === null) {
                    continue;
                }

                $values[] = $matchedValue;
            }

            if ($values) {
                $selected[$name] = $values;
            }
        }

        return $selected;
    }

    /**
     * @return int
     */
    public function getHitsPerPage(): int
    {
        $currentHitsPerPage = $this->request->getParam('product_list_limit', false);

        if ($currentHitsPerPage !== false) {
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
     * @param $urlKey
     * @return string
     */
    public function getProductUrl($urlKey): string
    {
        return $this->config['baseUrl'] . $urlKey . $this->config['productUrlSuffix'];
    }

    /**
     * @param $image
     * @return string
     */
    public function getProductImage($image): string
    {
        return $this->config['mediaBaseUrl'] . $image;
    }

    /**
     * @param $price
     * @return string
     */
    public function getProductPrice($price): string
    {
        return $this->priceCurrency->convertAndFormat($price);
    }
}
