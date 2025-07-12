<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\ResourceModel\Indexer\Category\Action;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\Data\CategoryInterface;

class Full
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param $storeId
     * @param array $categoryIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategories($storeId, array $categoryIds = []): array
    {
        $store = $this->storeManager->getStore($storeId);

        $categories = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addIsActiveFilter()
            ->addUrlRewriteToResult()
            ->addAttributeToSelect(['name', 'is_active', 'include_in_menu', 'image'])
            ->addAttributeToFilter('level', ['gt' => 1])
            ->addAttributeToFilter('path', ['like' => '1/' . $store->getRootCategoryId() . '/%'])
            ->addOrderField('entity_id');

        if (count($categoryIds) > 0) {
            $categories->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
        }

        $namesByPath = $this->getNamesByPath($categories->toArray());

        $data = [];
        /** @var CategoryInterface $category */
        foreach ($categories as $category) {
            $data[$category->getId()] = [
                'entity_id' => $category->getId(),
                'product_count' => $category->getProductCollection()->getSize(),
                'name' => $category->getName(),
                'url_key' => $category->getRequestPath(),
                'path' => $namesByPath[$category->getId()]
            ];
        }

        return $data;
    }

    /**
     * @param array $categories
     * @return array
     */
    private function getNamesByPath(array $categories): array
    {
        $result = [];

        foreach ($categories as $category) {
            $itemPath = explode('/', $category['path']);
            $names = array();

            foreach ($itemPath as $pathId) {
                if (isset($categories[$pathId]['name'])) {
                    $names[] = $categories[$pathId]['name'];
                }
            }

            $result[$category['entity_id']] = implode(' / ', $names);
        }

        return $result;
    }
}
