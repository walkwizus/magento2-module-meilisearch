<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributePostMapper\CatalogProduct;

use Magento\Framework\Exception\LocalizedException;
use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\SortFields;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;

class CategoryPosition implements AttributeMapperInterface
{
    /**
     * @param CategoryProductPosition $positionResource
     * @param SortFields $sortFields
     */
    public function __construct(
        private readonly CategoryProductPosition $positionResource,
        private readonly SortFields $sortFields
    ) {
    }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     * @throws LocalizedException
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        if ($this->positionResource->getConfiguredCategoryIds((int) $storeId) === []) {
            return $documentData;
        }

        $positions = $this->positionResource->getProductPositions((int) $storeId, array_keys($documentData));

        foreach ($positions as $productId => $categories) {
            if (!isset($documentData[$productId])) {
                continue;
            }

            foreach ($categories as $categoryId => $row) {
                if ($row['is_buried']) {
                    $documentData[$productId][$this->sortFields->getPositionField($categoryId)] =
                        SortFields::BURIED_POSITION_OFFSET + $row['position'];
                } else {
                    $documentData[$productId][$this->sortFields->getRankField($categoryId)] = $row['position'];
                }
            }
        }

        return $documentData;
    }
}
