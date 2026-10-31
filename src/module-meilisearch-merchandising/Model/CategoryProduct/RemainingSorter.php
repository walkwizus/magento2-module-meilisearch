<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryProduct;

use Magento\Framework\App\ResourceConnection;
use Walkwizus\MeilisearchBase\Service\SearchManager;

class RemainingSorter
{
    public const SORT_POSITION = 'position';
    public const SORT_NEWEST = 'newest';
    public const SORT_NAME = 'name';
    public const SORT_PRICE_ASC = 'price_asc';
    public const SORT_PRICE_DESC = 'price_desc';
    public const SORT_STOCK = 'stock';

    public const SORT_OPTIONS = [
        self::SORT_POSITION, self::SORT_NEWEST, self::SORT_NAME,
        self::SORT_PRICE_ASC, self::SORT_PRICE_DESC, self::SORT_STOCK
    ];

    private const PRICE_FIELD = 'price_0';

    /**
     * @param SearchManager $searchManager
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly SearchManager $searchManager,
        private readonly ResourceConnection $resourceConnection
    ) { }

    /**
     * @param string $sortBy
     * @return string
     */
    public function normalize(string $sortBy): string
    {
        return in_array($sortBy, self::SORT_OPTIONS, true) ? $sortBy : self::SORT_POSITION;
    }

    /**
     * @param array $productIds
     * @param string $sortBy
     * @param string $indexName
     * @return array
     * @throws \Exception
     */
    public function sort(array $productIds, string $sortBy, string $indexName): array
    {
        if ($productIds === [] || $this->normalize($sortBy) === self::SORT_POSITION) {
            return $productIds;
        }

        $keys = $sortBy === self::SORT_NEWEST
            ? $this->getCreatedAt($productIds)
            : $this->getDocumentKeys($productIds, $sortBy, $indexName);
        $ranks = array_flip($productIds);

        usort($productIds, function (int $a, int $b) use ($keys, $ranks, $sortBy): int {
            $comparison = $this->compare($keys[$a] ?? null, $keys[$b] ?? null, $sortBy);

            return $comparison !== 0 ? $comparison : $ranks[$a] <=> $ranks[$b];
        });

        return $productIds;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @param string $sortBy
     * @return int
     */
    private function compare(mixed $a, mixed $b, string $sortBy): int
    {
        if ($a === null || $b === null) {
            return ($a === null) <=> ($b === null);
        }

        return match ($sortBy) {
            self::SORT_NAME => strnatcasecmp((string) $a, (string) $b),
            self::SORT_PRICE_ASC => $a <=> $b,
            self::SORT_PRICE_DESC, self::SORT_NEWEST, self::SORT_STOCK => $b <=> $a,
            default => 0
        };
    }

    /**
     * @param array $productIds
     * @return array
     */
    private function getCreatedAt(array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id', 'created_at'])
            ->where('entity_id IN (?)', $productIds);

        return array_map('strval', $connection->fetchPairs($select));
    }

    /**
     * @param array $productIds
     * @param string $sortBy
     * @param string $indexName
     * @return array
     * @throws \Exception
     */
    private function getDocumentKeys(array $productIds, string $sortBy, string $indexName): array
    {
        $keys = [];

        foreach (array_chunk($productIds, 500) as $chunk) {
            $result = $this->searchManager->search($indexName, '', [
                'filter' => 'id IN [' . implode(',', $chunk) . ']',
                'attributesToRetrieve' => ['id', 'name', self::PRICE_FIELD, 'stock'],
                'limit' => count($chunk)
            ]);

            foreach ($result->getHits() as $hit) {
                $keys[(int) $hit['id']] = match ($sortBy) {
                    self::SORT_NAME => $hit['name'] ?? null,
                    self::SORT_PRICE_ASC, self::SORT_PRICE_DESC => isset($hit[self::PRICE_FIELD]) ? (float) $hit[self::PRICE_FIELD] : null,
                    self::SORT_STOCK => $this->getStockKey($hit['stock'] ?? null),
                    default => null
                };
            }
        }

        return $keys;
    }

    /**
     * @param mixed $stock
     * @return float|null
     */
    private function getStockKey(mixed $stock): ?float
    {
        if (!is_array($stock)) {
            return null;
        }

        $inStock = ($stock['is_in_stock'] ?? true) !== false;

        return ($inStock ? 1000000000 : 0) + (float) ($stock['qty'] ?? 0);
    }
}
