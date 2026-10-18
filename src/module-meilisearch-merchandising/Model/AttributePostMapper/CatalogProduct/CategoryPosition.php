<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributePostMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;

class CategoryPosition implements AttributeMapperInterface
{
    private const UNARRANGED_POSITION_OFFSET = 500000;

    /**
     * @param CategoryProductPosition $positionResource
     */
    public function __construct(
        private readonly CategoryProductPosition $positionResource
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        $configuredCategoryIds = array_flip($this->positionResource->getConfiguredCategoryIds((int) $storeId));

        if ($configuredCategoryIds === []) {
            return $documentData;
        }

        $positions = $this->positionResource->getProductPositions((int) $storeId, array_keys($documentData));

        foreach ($documentData as $productId => &$productData) {
            foreach ((array) ($productData['category_ids'] ?? []) as $categoryId) {
                $categoryId = (int) $categoryId;

                if (!isset($configuredCategoryIds[$categoryId])) {
                    continue;
                }

                $field = 'position_category_' . $categoryId;
                $productData[$field] = $positions[(int) $productId][$categoryId]
                    ?? self::UNARRANGED_POSITION_OFFSET + (int) ($productData[$field] ?? 0);
            }
        }

        return $documentData;
    }
}
