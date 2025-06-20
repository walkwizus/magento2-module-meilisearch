<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Helper\Data as MeilisearchFrontendHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Media\Config as ProductMediaConfig;

class UrlConfigProvider implements ConfigProviderInterface
{
    /**
     * @param MeilisearchFrontendHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param ProductMediaConfig $productMediaConfig
     */
    public function __construct(
        private readonly MeilisearchFrontendHelper $helper,
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
        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $baseMediaPath = $this->productMediaConfig->getBaseMediaPath();

        return [
            'baseUrl' => $baseUrl,
            'productUrlSuffix' => $this->helper->getProductUrlSuffix(),
            'productUseCategories' => $this->helper->getProductUseCategories(),
            'mediaBaseUrl' => $mediaBaseUrl . $baseMediaPath
        ];
    }
}
