<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;
use Walkwizus\MeilisearchFrontend\Model\FragmentAggregator;
use Magento\Framework\View\ConfigInterface as ViewConfig;

class CatalogStoreFrontConfigProvider implements ConfigProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreFront $storeFront
     * @param FragmentAggregator $fragmentAggregator
     * @param ViewConfig $viewConfig
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreFront $storeFront,
        private readonly FragmentAggregator $fragmentAggregator,
        private readonly ViewConfig $viewConfig
    ) { }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = $this->storeManager->getStore()->getId();

        $config = $this->viewConfig->getViewConfig()->getMediaEntities('Magento_Catalog', 'images');

        return [
            'listMode' => $this->storeFront->getListMode($storeId),
            'gridPerPageValues' => array_map('intval', explode(',', $this->storeFront->getGridPerPageValues($storeId))),
            'gridPerPage' => (int)$this->storeFront->getGridPerPage($storeId),
            'listPerPageValues' => array_map('intval', explode(',', $this->storeFront->getListPerPageValues($storeId))),
            'listPerPage' => (int)$this->storeFront->getListPerPage($storeId),
            'listAllowAll' => (bool)$this->storeFront->getListAllowAll($storeId),
            'showSwatchesInProductList' => (bool)$this->storeFront->getShowSwatchesInProductList($storeId),
            'fragments' => $this->fragmentAggregator->getFragmentsCode(),
            'images' => [
                'category_page_grid' => $config['category_page_grid'],
                'category_page_list' => $config['category_page_list'],
                'mini_cart_product_thumbnail' => $config['mini_cart_product_thumbnail'],
            ]
        ];
    }
}
