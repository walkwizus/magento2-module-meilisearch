<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\App\ResourceConnection;
use Meilisearch\Contracts\DocumentsQuery;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\SortFields;

class SaveProductPosition
{
    private const INDEX_TYPE = 'catalog_product';
    private const BATCH_SIZE = 1000;

    /**
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param DocumentsManager $documentsManager
     * @param ConnectionManager $connectionManager
     * @param ResourceConnection $resourceConnection
     * @param TableMaintainer $tableMaintainer
     * @param SortFields $sortFields
     */
    public function __construct(
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly DocumentsManager $documentsManager,
        private readonly ConnectionManager $connectionManager,
        private readonly ResourceConnection $resourceConnection,
        private readonly TableMaintainer $tableMaintainer,
        private readonly SortFields $sortFields
    ) {
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $productIds
     * @param array $rows
     * @return void
     * @throws \Exception
     */
    public function execute(int $storeId, int $categoryId, array $productIds, array $rows): void
    {
        $indexName = $this->searchIndexNameResolver->getIndexName($storeId, self::INDEX_TYPE);
        $productIds = $this->getIndexedIds($indexName, $productIds);

        if ($productIds === []) {
            return;
        }

        $rankField = $this->sortFields->getRankField($categoryId);
        $positionField = $this->sortFields->getPositionField($categoryId);
        $nativePositions = $this->getNativePositions($storeId, $categoryId, $productIds);
        $rowsByProduct = array_column($rows, null, 'product_id');
        $updates = [];
        $replacements = [];

        foreach ($productIds as $productId) {
            $row = $rowsByProduct[$productId] ?? null;
            $isBuried = $row !== null && $row['is_buried'];
            $fields = [
                $rankField => $row !== null && !$isBuried ? (int) $row['position'] : null,
                $positionField => $isBuried
                    ? SortFields::BURIED_POSITION_OFFSET + (int) $row['position']
                    : $nativePositions[$productId] ?? null
            ];

            if (in_array(null, $fields, true)) {
                $replacements[$productId] = $fields;
            } else {
                $updates[] = ['id' => $productId] + $fields;
            }
        }

        if ($updates !== []) {
            $this->documentsManager->updateDocuments($indexName, $updates);
        }

        if ($replacements !== []) {
            $this->documentsManager->addDocuments($indexName, $this->getReplacements($indexName, $replacements), 'id');
        }
    }

    /**
     * @param string $indexName
     * @param array $fieldsByProduct
     * @return array
     * @throws \Exception
     */
    private function getReplacements(string $indexName, array $fieldsByProduct): array
    {
        $index = $this->connectionManager->getConnection()->index($indexName);
        $documents = [];

        foreach (array_chunk(array_keys($fieldsByProduct), self::BATCH_SIZE) as $chunk) {
            $results = $index->getDocuments(
                (new DocumentsQuery())
                    ->setIds(array_map('strval', $chunk))
                    ->setLimit(count($chunk))
            )->getResults();

            foreach ($results as $document) {
                foreach ($fieldsByProduct[(int) $document['id']] as $field => $value) {
                    if ($value === null) {
                        unset($document[$field]);
                    } else {
                        $document[$field] = $value;
                    }
                }
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * @param string $indexName
     * @param array $productIds
     * @return array
     * @throws \Exception
     */
    private function getIndexedIds(string $indexName, array $productIds): array
    {
        $index = $this->connectionManager->getConnection()->index($indexName);
        $indexedIds = [];

        foreach (array_chunk($productIds, self::BATCH_SIZE) as $chunk) {
            $documents = $index->getDocuments(
                (new DocumentsQuery())
                    ->setIds(array_map('strval', $chunk))
                    ->setFields(['id'])
                    ->setLimit(count($chunk))
            )->getResults();

            foreach ($documents as $document) {
                $indexedIds[] = (int) $document['id'];
            }
        }

        return $indexedIds;
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $productIds
     * @return array
     */
    private function getNativePositions(int $storeId, int $categoryId, array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->tableMaintainer->getMainTable($storeId), ['product_id', 'position'])
            ->where('store_id = ?', $storeId)
            ->where('category_id = ?', $categoryId)
            ->where('product_id IN (?)', $productIds);

        return array_map('intval', $connection->fetchPairs($select));
    }
}
