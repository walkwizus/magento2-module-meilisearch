<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Walkwizus\MeilisearchFrontend\Model\Search\SearchService;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Ssr implements ArgumentInterface
{
    /**
     * @var array
     */
    private array $config;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ConfigProvider $configProvider,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly SearchService $searchService,
        private readonly CollectionFactory $collectionFactory
    ) {
        $this->config = $this->configProvider->get();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getSearchResult(): array
    {
        return $this->searchService->getSearchResult();
    }

    /**
     * @param $searchResult
     * @return Collection
     */
    public function getProductCollection($searchResult): Collection
    {
        $hits = $searchResult['hits'] ?? [];

        $entityIds = array_values(array_filter(array_map(
            static fn(array $hit): int => (int)($hit['id'] ?? 0),
            $hits
        )));

        $collection = $this->collectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $entityIds])
            ->addUrlRewrite();

        $idList = implode(',', $entityIds);
        $collection->getSelect()->order(new \Zend_Db_Expr("FIELD(e.entity_id, $idList)"));

        return $collection;
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
        return $this->searchService->getSortBy();
    }

    /**
     * @return bool
     */
    public function isDescending(): bool
    {
        return $this->searchService->isDescending();
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
