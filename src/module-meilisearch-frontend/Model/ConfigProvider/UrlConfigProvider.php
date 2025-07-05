<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\SearchEngineOptimization;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class UrlConfigProvider implements ConfigProviderInterface
{
    /**
     * @param SearchEngineOptimization $searchEngineOptimization
     * @param StoreManagerInterface $storeManager
     * @param ProductMediaConfig $productMediaConfig
     */
    public function __construct(
        private readonly SearchEngineOptimization $searchEngineOptimization,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductMediaConfig $productMediaConfig
    ) { }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $baseMediaPath = $this->productMediaConfig->getBaseMediaPath();

        return [
            'baseUrl' => $baseUrl,
            'productUrlSuffix' => $this->searchEngineOptimization->getProductUrlSuffix(),
            'productUseCategories' => $this->searchEngineOptimization->getProductUseCategories(),
            'mediaBaseUrl' => $mediaBaseUrl . $baseMediaPath
        ];
    }
}
