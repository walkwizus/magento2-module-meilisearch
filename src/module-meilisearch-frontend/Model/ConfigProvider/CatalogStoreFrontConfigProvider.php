<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;
use Walkwizus\MeilisearchFrontend\Model\FragmentAggregator;

class CatalogStoreFrontConfigProvider implements ConfigProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreFront $storeFront
     * @param FragmentAggregator $fragmentAggregator
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreFront $storeFront,
        private readonly FragmentAggregator $fragmentAggregator
    ) { }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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
            'showSwatchesInProductList' => (bool)$this->storeFront->getShowSwatchesInProductList($storeId),
            'fragments' => $this->fragmentAggregator->getFragmentsCode(),
        ];
    }
}
