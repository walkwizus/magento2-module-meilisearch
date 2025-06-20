<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Inventory implements AttributeMapperInterface
{
    /**
     * @var array|null
     */
    protected ?array $stockIdByWebsite = null;

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly StockResolverInterface $stockResolver,
        private readonly StockIndexTableNameResolverInterface $stockIndexTableNameResolver
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function map(array $documentData, $storeId): array
    {
        $indexData = [];
        $inventoryData = $this->loadInventoryData($storeId, array_keys($documentData));

        foreach ($inventoryData as $inventoryDatum) {
            $productId = (int) $inventoryDatum['product_id'];
            $indexData[$productId]['stock'] = [
                'is_in_stock' => (bool)$inventoryDatum['stock_status'],
                'qty' => (int)$inventoryDatum['qty'],
            ];
        }

        return $indexData;
    }

    /**
     * @param $storeId
     * @param $productIds
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function loadInventoryData($storeId, $productIds): array
    {
        $websiteId = $this->getWebsiteId($storeId);
        $stockId = $this->getStockId($websiteId);
        $tableName = $this->stockIndexTableNameResolver->execute($stockId);
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(['product' => $connection->getTableName('catalog_product_entity')], [])
            ->join(
                ['stock_index' => $tableName],
                'product.sku = stock_index.' . IndexStructure::SKU,
                [
                    'product_id' => 'product.entity_id',
                    'stock_status' => 'stock_index.' . IndexStructure::IS_SALABLE,
                    'qty' => 'stock_index.' . IndexStructure::QUANTITY,
                ]
            )
            ->where('product.entity_id IN (?)', $productIds)
            ->group('product.entity_id');

        return $connection->fetchAll($select);
    }

    /**
     * @param $websiteId
     * @return int|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getStockId($websiteId): mixed
    {
        if (!isset($this->stockIdByWebsite[$websiteId])) {
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $stockId = (int) $stock->getStockId();
            $this->stockIdByWebsite[$websiteId] = $stockId;
        }

        return $this->stockIdByWebsite[$websiteId];
    }

    /**
     * @param $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    private function getWebsiteId($storeId): int
    {
        return (int)$this->storeManager->getStore($storeId)->getWebsiteId();
    }
}
