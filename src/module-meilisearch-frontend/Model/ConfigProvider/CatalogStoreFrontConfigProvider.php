<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;

class CatalogStoreFrontConfigProvider implements ConfigProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreFront $storeFront
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreFront $storeFront,
    ) { }

    public function get(): array
    {
        $storeId = $this->storeManager->getStore()->getId();

        return [
            'listMode' => $this->storeFront->getListMode($storeId),
            'gridPerPageValues' => array_map('intval', explode(',', $this->storeFront->getGridPerPageValues($storeId))),
            'gridPerPage' => (int)$this->storeFront->getGridPerPage($storeId),
            'listPerPageValues' => array_map('intval', explode(',', $this->storeFront->getListPerPageValues($storeId))),
            'listPerPage' => (int)$this->storeFront->getListPerPage($storeId),
            'listAllowAll' => (bool)$this->storeFront->getListAllowAll($storeId),
            'showSwatchesInProductList' => (bool)$this->storeFront->getShowSwatchesInProductList($storeId)
        ];
    }
}
