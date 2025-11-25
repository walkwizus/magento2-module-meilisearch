<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Meilisearch\Search\SearchResult;

class Ssr implements ArgumentInterface
{
    /**
     * @var array
     */
    private array $config;

    /**
     * @param RequestInterface $request
     * @param SearchManager $searchManager
     * @param ConfigProvider $configProvider
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SearchManager $searchManager,
        private readonly ConfigProvider $configProvider,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
        $this->config = $this->configProvider->get();
    }

    /**
     * @return array|SearchResult
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSearchResult(): array|SearchResult
    {
        $query = $this->request->getParam('q', '');
        $currentPage = (int)$this->request->getParam('page', 1);

        $defaultSortBy = $this->config['defaultSortBy'];
        $indexName = $this->config['indexName'];
        $facets = $this->config['facets']['facetList'];

        $filters = $this->buildFilters();

        return $this->searchManager->search($indexName, $query, [
            'filter' => $filters,
            'facets' => $facets,
            'sort' => [$defaultSortBy . ':asc'],
            'page' => $currentPage,
            'hitsPerPage' => $this->getHitsPerPage()
        ]);
    }

    /**
     * @return array
     */
    public function buildFilters(): array
    {
        $filters = [];

        $categoryId = (int)($this->config['currentCategoryId'] ?? 0);
        if ($categoryId > 0) {
            $filters[] = ['category_ids = ' . $categoryId];
        }

        $selectedFacets = $this->getSelectedFacets();

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
