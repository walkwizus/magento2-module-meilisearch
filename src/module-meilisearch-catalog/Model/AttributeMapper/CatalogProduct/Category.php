<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;

class Category implements AttributeMapperInterface
{

    /**
     * @param ResourceConnection $resourceConnection
     * @param TableMaintainer $tableMaintainer
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly TableMaintainer $tableMaintainer
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        $documents = [];
        $productIds = array_keys($documentData);
        $categoriesData = $this->getProductsCategories($productIds, $storeId);

        foreach ($categoriesData as $row) {
            $productId = (int)$row['product_id'];
            $categoryId = (int)$row['category_id'];
            $position = (int)($row['position'] ?? 0);

            $documents[$productId]['category_ids'][] = $categoryId;
            $documents[$productId]['position_category_' . $categoryId] = $position;
        }

        return $documents;
    }

    /**
     * @param array $productIds
     * @param $storeId
     * @return array
     */
    protected function getProductsCategories(array $productIds, $storeId): array
    {
        $mainTable = $this->tableMaintainer->getMainTable((int)$storeId);
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(['cpi' => $mainTable], ['product_id', 'category_id', 'position'])
            ->where('cpi.store_id = ?', $storeId)
            ->where('cpi.product_id IN (?)', $productIds);

        return $this->resourceConnection->getConnection()->fetchAll($select);
    }
}
