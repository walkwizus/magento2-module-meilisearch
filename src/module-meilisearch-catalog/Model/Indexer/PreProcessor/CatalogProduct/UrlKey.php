<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\Indexer\PreProcessor\CatalogProduct;

use Magento\Framework\App\ResourceConnection;
use Walkwizus\MeilisearchBase\Api\IndexerPreProcessorInterface;

class UrlKey implements IndexerPreProcessorInterface
{
    public const CONTEXT_PRODUCT_URL_KEYS = 'product_url_keys';
    private const PRODUCT_URL_FALLBACK_PATTERN = 'catalog/product/view/id/%d';

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) { }

    /**
     * @param string $indexerId
     * @param array $documentIds
     * @param int $storeId
     * @return array{product_url_keys: array<int, string>}
     */
    public function prepare(string $indexerId, array $documentIds, int $storeId)
    {
        $productIds = array_map('intval', array_keys($documentIds));
        if (empty($productIds)) {
            return [self::CONTEXT_PRODUCT_URL_KEYS => []];
        }

        $productRewritePaths = $this->getProductRewritePaths($productIds, $storeId);
        $productUrlKeys = [];

        foreach ($productIds as $productId) {
            $productUrlKeys[$productId] = $this->resolveProductUrlKey($productId, $productRewritePaths);
        }

        return [self::CONTEXT_PRODUCT_URL_KEYS => $productUrlKeys];
    }

    /**
     * @param int[] $productIds
     * @param int $storeId
     * @return array<int, string>
     */
    private function getProductRewritePaths(array $productIds, int $storeId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['url_rewrite' => $connection->getTableName('url_rewrite')],
                ['entity_id', 'request_path', 'metadata']
            )
            ->where('url_rewrite.entity_type = ?', 'product')
            ->where('url_rewrite.store_id = ?', $storeId)
            ->where('url_rewrite.entity_id IN (?)', $productIds)
            ->where('url_rewrite.redirect_type = ?', 0)
            ->where('url_rewrite.request_path IS NOT NULL')
            ->where("url_rewrite.request_path <> ''");

        $rows = $connection->fetchAll($select);
        $bestByProduct = [];

        foreach ($rows as $row) {
            $entityId = (int) ($row['entity_id'] ?? 0);
            $requestPath = ltrim((string) ($row['request_path'] ?? ''), '/');

            if ($entityId <= 0 || $requestPath === '') {
                continue;
            }

            if (!isset($bestByProduct[$entityId])) {
                $bestByProduct[$entityId] = [
                    'request_path' => $requestPath,
                    'metadata' => (string) ($row['metadata'] ?? ''),
                ];
                continue;
            }

            if ($this->isPreferredRewritePath($requestPath, (string) ($row['metadata'] ?? ''), $bestByProduct[$entityId])) {
                $bestByProduct[$entityId] = [
                    'request_path' => $requestPath,
                    'metadata' => (string) ($row['metadata'] ?? ''),
                ];
            }
        }

        return array_map(
            static fn(array $row): string => (string) $row['request_path'],
            $bestByProduct
        );
    }

    /**
     * @param string $candidatePath
     * @param string $candidateMetadata
     * @param array{request_path: string, metadata: string} $current
     * @return bool
     */
    private function isPreferredRewritePath(string $candidatePath, string $candidateMetadata, array $current): bool
    {
        $candidateHasCategory = str_contains($candidateMetadata, '"category_id"');
        $currentHasCategory = str_contains((string) $current['metadata'], '"category_id"');

        if ($candidateHasCategory !== $currentHasCategory) {
            return !$candidateHasCategory;
        }

        return strlen($candidatePath) < strlen((string) $current['request_path']);
    }

    /**
     * @param int $productId
     * @param array<int, string> $productRewritePaths
     * @return string
     */
    private function resolveProductUrlKey(int $productId, array $productRewritePaths): string
    {
        if (isset($productRewritePaths[$productId]) && $productRewritePaths[$productId] !== '') {
            return $productRewritePaths[$productId];
        }

        return sprintf(self::PRODUCT_URL_FALLBACK_PATTERN, $productId);
    }
}
