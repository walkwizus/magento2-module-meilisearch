<?php

declare(strict_types=1);

namespace Walkwizus\MeiliSearchMerchandising\Model\AttributePostMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\Category\CollectionFactory;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;
use Walkwizus\MeilisearchMerchandising\Api\Data\CategoryInterface;

class Category implements AttributeMapperInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param QueryBuilderService $queryBuilderService
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly QueryBuilderService $queryBuilderService
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @return array
     */
    public function map(array $documentData, $storeId): array
    {
        $virtualCategories = $this->collectionFactory->create()
            ->addFieldToFilter('store_id', $storeId);

        if (!$virtualCategories->getSize()) {
            return $documentData;
        }

        $enrichedDocuments = [];

        foreach ($documentData as $productId => $productData) {
            $newProductData = $productData;
            $categoryIds = $newProductData['category_ids'] ?? [];

            /** @var CategoryInterface $virtualCategory */
            foreach ($virtualCategories as $virtualCategory) {
                $ruleArray = json_decode($virtualCategory->getQuery(), true);

                if ($ruleArray && $this->queryBuilderService->isMatch($productData, $ruleArray)) {
                    $vCatId = (int)$virtualCategory->getCategoryId();
                    $categoryIds[] = $vCatId;
                }
            }

            if (!empty($categoryIds)) {
                $newProductData['category_ids'] = array_unique($categoryIds);
            }

            $enrichedDocuments[$productId] = $newProductData;
        }

        return $enrichedDocuments;
    }
}
