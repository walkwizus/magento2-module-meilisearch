<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CategoryProductPosition extends AbstractDb
{
    /**
     * @var array
     */
    private array $configuredCategoryIds = [];

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('meilisearch_merchandising_category_product', 'id');
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return array
     * @throws LocalizedException
     */
    public function getCategoryPositions(int $storeId, int $categoryId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['product_id', 'position', 'is_pinned', 'is_buried'])
            ->where('store_id = ?', $storeId)
            ->where('category_id = ?', $categoryId)
            ->order('position ASC');

        $positions = [];
        foreach ($connection->fetchAll($select) as $row) {
            $positions[(int) $row['product_id']] = [
                'position' => (int) $row['position'],
                'is_pinned' => (bool) $row['is_pinned'],
                'is_buried' => (bool) $row['is_buried']
            ];
        }

        return $positions;
    }

    /**
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    public function getConfiguredCategoryIds(int $storeId): array
    {
        if (!isset($this->configuredCategoryIds[$storeId])) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->distinct()
                ->from($this->getMainTable(), ['category_id'])
                ->where('store_id = ?', $storeId);

            $this->configuredCategoryIds[$storeId] = array_map('intval', $connection->fetchCol($select));
        }

        return $this->configuredCategoryIds[$storeId];
    }

    /**
     * @param int $storeId
     * @param array $productIds
     * @return array
     * @throws LocalizedException
     */
    public function getProductPositions(int $storeId, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['product_id', 'category_id', 'position'])
            ->where('store_id = ?', $storeId)
            ->where('product_id IN (?)', array_map('intval', $productIds));

        $positions = [];
        foreach ($connection->fetchAll($select) as $row) {
            $positions[(int) $row['product_id']][(int) $row['category_id']] = (int) $row['position'];
        }

        return $positions;
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $rows
     * @return void
     * @throws \Exception
     */
    public function replaceCategoryPositions(int $storeId, int $categoryId, array $rows): void
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $connection->delete($this->getMainTable(), ['store_id = ?' => $storeId, 'category_id = ?' => $categoryId]);

            foreach (array_chunk($rows, 500) as $chunk) {
                $connection->insertMultiple($this->getMainTable(), array_map(
                    static fn (array $row): array => [
                        'store_id' => $storeId,
                        'category_id' => $categoryId,
                        'product_id' => (int) $row['product_id'],
                        'position' => (int) $row['position'],
                        'is_pinned' => (int) $row['is_pinned'],
                        'is_buried' => (int) $row['is_buried']
                    ],
                    $chunk
                ));
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        unset($this->configuredCategoryIds[$storeId]);
    }
}
