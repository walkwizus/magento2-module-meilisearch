<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryProduct;

class PinRuleBuilder
{
    public const PRECEDENCE = 10;

    private const UID_SEPARATOR = '-category-';

    /**
     * @param string $indexName
     * @return string
     */
    public function getUidPrefix(string $indexName): string
    {
        return $indexName . self::UID_SEPARATOR;
    }

    /**
     * @param string $indexName
     * @param int $categoryId
     * @return string
     */
    public function getUid(string $indexName, int $categoryId): string
    {
        return $this->getUidPrefix($indexName) . $categoryId;
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public function getConditions(int $categoryId): array
    {
        return [
            'query' => ['isEmpty' => true],
            'filter' => ['values' => ['category_ids' => $categoryId]]
        ];
    }

    /**
     * @param string $indexName
     * @param array $pins
     * @return array
     */
    public function getActions(string $indexName, array $pins): array
    {
        asort($pins);
        $actions = [];

        foreach ($pins as $productId => $slot) {
            $actions[] = [
                'id' => (string) $productId,
                'position' => max(0, (int) $slot - 1),
                'indexUid' => $indexName
            ];
        }

        return ['pin' => $actions];
    }

    /**
     * @param int $categoryId
     * @return string
     */
    public function getDescription(int $categoryId): string
    {
        return sprintf('Magento category %d pinned products', $categoryId);
    }
}
