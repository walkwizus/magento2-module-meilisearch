<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributePostMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;
use Magento\Framework\Exception\LocalizedException;

class Category implements AttributeMapperInterface
{
    /**
     * @param CollectionFactory $virtualCategoryCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param QueryBuilderService $queryBuilderService
     */
    public function __construct(
        private readonly CollectionFactory $virtualCategoryCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly QueryBuilderService $queryBuilderService
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @return array
     * @throws LocalizedException
     */
    public function map(array $documentData, $storeId): array
    {
        $virtualCategories = $this->virtualCategoryCollectionFactory
            ->create()
            ->addFieldToFilter('store_id', $storeId);

        $priorities = $this->getCategoriesPriorities();

        foreach ($documentData as $productId => &$productData) {
            $categoryIds = $productData['category_ids'] ?? [];
            $matchedVirtualIds = [];

            foreach ($virtualCategories as $vCat) {
                $rule = json_decode($vCat->getQuery(), true);
                if ($rule && $this->queryBuilderService->isMatch($productData, $rule)) {
                    $matchedVirtualIds[] = (int)$vCat->getCategoryId();
                }
            }

            $allIds = array_unique(array_merge($categoryIds, $matchedVirtualIds));

            if (!empty($allIds)) {
                usort($allIds, function ($a, $b) use ($priorities) {
                    $prioA = $priorities[$a] ?? 0;
                    $prioB = $priorities[$b] ?? 0;

                    if ($prioA === $prioB) {
                        return $a <=> $b;
                    }

                    return $prioA <=> $prioB;
                });

                $productData['category_ids'] = array_values($allIds);
            }
        }

        return $documentData;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getCategoriesPriorities(): array
    {
        $collection = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToSelect('breadcrumb_priority');

        $data = [];
        foreach ($collection as $category) {
            $data[(int)$category->getId()] = (int)$category->getData('breadcrumb_priority');
        }

        return $data;
    }
}
