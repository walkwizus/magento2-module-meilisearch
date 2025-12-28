<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\Media;

use Magento\Catalog\Model\Config\CatalogMediaConfig;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject;

class ImageCacheHashResolver
{
    /**
     * @param CatalogMediaConfig $catalogMediaConfig
     * @param ImageHelper $imageHelper
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CatalogMediaConfig $catalogMediaConfig,
        private readonly ImageHelper $imageHelper,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) { }

    /**
     * @param string $imageId
     * @param $attribute
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function resolve(string $imageId, $attribute): ?string
    {
        if ($this->catalogMediaConfig->getMediaUrlFormat() !== CatalogMediaConfig::HASH) {
            return null;
        }

        $product = $this->getRandomProduct($attribute);
        if(!$product) {
            return null;
        }

        $url = $this->imageHelper->init($product, $imageId)->getUrl();
        $hash = null;
        if (preg_match('#/cache/([^/]+)/#', $url, $matches)) {
            $hash = $matches[1];
        }

        return $hash;
    }

    /**
     * @param $attribute
     * @return DataObject|null
     * @throws NoSuchEntityException
     */
    private function getRandomProduct($attribute): ?DataObject
    {
        $storeId = $this->storeManager->getStore()->getId();

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToSelect([$attribute]);
        $collection->addAttributeToFilter($attribute, ['nin' => ['no_selection', '']]);
        $collection->addAttributeToFilter('status', 1);
        $collection->addAttributeToFilter('visibility', ['in' => [
            Visibility::VISIBILITY_IN_CATALOG,
            Visibility::VISIBILITY_IN_SEARCH,
            Visibility::VISIBILITY_BOTH
        ]]);
        $collection->setPageSize(1)->setCurPage(1);

        return $collection->getFirstItem()?->getId() ? $collection->getFirstItem() : null;
    }
}
