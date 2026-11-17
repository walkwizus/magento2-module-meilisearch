<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryProduct;

class SortFields
{
    public const RANK_PREFIX = 'merch_rank_category_';
    public const POSITION_PREFIX = 'position_category_';
    public const BURIED_POSITION_OFFSET = 10000000;

    /**
     * @param int $categoryId
     * @return string
     */
    public function getRankField(int $categoryId): string
    {
        return self::RANK_PREFIX . $categoryId;
    }

    /**
     * @param int $categoryId
     * @return string
     */
    public function getPositionField(int $categoryId): string
    {
        return self::POSITION_PREFIX . $categoryId;
    }

    /**
     * @param int $categoryId
     * @param string $direction
     * @return string[]
     */
    public function getSort(int $categoryId, string $direction = 'asc'): array
    {
        return [
            $this->getRankField($categoryId) . ':' . $direction,
            $this->getPositionField($categoryId) . ':' . $direction
        ];
    }
}
