<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Search\Request\Dimension;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchBase\Model\Adapter\Meilisearch as MeilisearchAdapter;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Walkwizus\MeilisearchBase\Model\AttributeMapper;

class BaseIndexerHandler implements IndexerInterface
{
    /**
     * @param MeilisearchAdapter $meilisearchAdapter
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param Batch $batch
     * @param string $indexerId
     * @param AttributeMapper $attributeMapper
     * @param AttributeProvider $attributeProvider
     * @param int $batchSize
     * @param string $indexPrimaryKey
     */
    public function __construct(
        private readonly MeilisearchAdapter $meilisearchAdapter,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly Batch $batch,
        private readonly string $indexerId,
        private readonly AttributeMapper $attributeMapper,
        private readonly AttributeProvider $attributeProvider,
        private readonly int $batchSize = 10000,
        private readonly string $indexPrimaryKey = 'id'
    ) { }

    /**
     * @param Dimension[] $dimensions
     * @param \Traversable $documents
     * @return IndexerInterface
     */
    public function saveIndex($dimensions, \Traversable $documents): IndexerInterface
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            $this->meilisearchAdapter->updateSettings($indexName, [
                'filterableAttributes' => $this->attributeProvider->getFilterableAttributes($indexerId, 'index'),
                'sortableAttributes' => $this->attributeProvider->getSortableAttributes($indexerId, 'index'),
                'searchableAttributes' => $this->attributeProvider->getSearchableAttributes($indexerId, 'index'),
            ]);

            foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
                $batchDocuments = $this->attributeMapper->map($indexerId, $batchDocuments, $storeId);
                try {
                    $this->meilisearchAdapter->addDocs($indexName, $batchDocuments, $this->indexPrimaryKey);
                } catch (\Exception $e) { }
            }
        }

        return $this;
    }

    /**
     * @param $dimensions
     * @param \Traversable $documents
     * @return void
     */
    public function deleteIndex($dimensions, \Traversable $documents): void
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            $this->meilisearchAdapter->deleteIndex($indexName);
        }
    }

    /**
     * @param $dimensions
     * @return void
     */
    public function cleanIndex($dimensions): void
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            $this->meilisearchAdapter->deleteIndex($indexName);
            $this->meilisearchAdapter->createIndex($indexName);
        }
    }

    /**
     * @param Dimension[] $dimensions
     * @return bool
     */
    public function isAvailable($dimensions = []): bool
    {
        return $this->meilisearchAdapter->isHealthy();
    }

    /**
     * @return string
     */
    public function getIndexerId(): string
    {
        return $this->searchIndexNameResolver->getIndexMapping($this->indexerId);
    }
}
