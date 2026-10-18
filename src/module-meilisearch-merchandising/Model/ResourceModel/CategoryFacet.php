<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CategoryFacet extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('meilisearch_merchandising_category_facet', 'id');
    }

    /**
     * @param int $storeId
     * @param array $categoryIds
     * @return array
     * @throws LocalizedException
     */
    public function getOrderings(int $storeId, array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['category_id', 'facet_ordering'])
            ->where('store_id = ?', $storeId)
            ->where('category_id IN (?)', array_map('intval', $categoryIds));

        $orderings = [];
        foreach ($connection->fetchPairs($select) as $categoryId => $facetOrdering) {
            $ordering = json_decode((string) $facetOrdering, true);

            if (is_array($ordering)) {
                $orderings[(int) $categoryId] = $ordering;
            }
        }

        return $orderings;
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $ordering
     * @return void
     * @throws LocalizedException
     */
    public function saveOrdering(int $storeId, int $categoryId, array $ordering): void
    {
        $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            [
                'store_id' => $storeId,
                'category_id' => $categoryId,
                'facet_ordering' => json_encode($ordering)
            ],
            ['facet_ordering']
        );
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return void
     * @throws LocalizedException
     */
    public function deleteOrdering(int $storeId, int $categoryId): void
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['store_id = ?' => $storeId, 'category_id = ?' => $categoryId]
        );
    }
}
