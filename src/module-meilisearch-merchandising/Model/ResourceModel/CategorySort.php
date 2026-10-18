<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CategorySort extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('meilisearch_merchandising_category_sort', 'id');
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return string|null
     * @throws LocalizedException
     */
    public function getSortBy(int $storeId, int $categoryId): ?string
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['sort_by'])
            ->where('store_id = ?', $storeId)
            ->where('category_id = ?', $categoryId);

        $sortBy = (string) $connection->fetchOne($select);

        return $sortBy !== '' ? $sortBy : null;
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param string $sortBy
     * @return void
     * @throws LocalizedException
     */
    public function saveSortBy(int $storeId, int $categoryId, string $sortBy): void
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            ['store_id' => $storeId, 'category_id' => $categoryId, 'sort_by' => $sortBy],
            ['sort_by']
        );
    }
}
