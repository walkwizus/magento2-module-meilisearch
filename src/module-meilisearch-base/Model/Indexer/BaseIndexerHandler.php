<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Search\Model\EngineResolver;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;
use Walkwizus\MeilisearchBase\Service\IndexesManager;
use Walkwizus\MeilisearchBase\Service\HealthManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Walkwizus\MeilisearchBase\Model\AttributeMapper;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Magento\Framework\Search\Request\Dimension;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;

class BaseIndexerHandler implements IndexerInterface
{
    /**
     * @param EngineResolver $engineResolver
     * @param SettingsManager $settingsManager
     * @param DocumentsManager $documentsManager
     * @param IndexesManager $indexesManager
     * @param HealthManager $healthManager
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param Batch $batch
     * @param string $indexerId
     * @param AttributeMapper $attributeMapper
     * @param AttributeProvider $attributeProvider
     * @param int $batchSize
     * @param string $indexPrimaryKey
     */
    public function __construct(
        private readonly EngineResolver $engineResolver,
        private readonly SettingsManager $settingsManager,
        private readonly DocumentsManager $documentsManager,
        private readonly IndexesManager $indexesManager,
        private readonly HealthManager $healthManager,
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
     * @throws \Exception
     */
    public function saveIndex($dimensions, \Traversable $documents): IndexerInterface
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            try {
                $this->settingsManager->updateFilterableAttributes($indexName, $this->attributeProvider->getFilterableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSortableAttributes($indexName, $this->attributeProvider->getSortableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSearchableAttributes($indexName, $this->attributeProvider->getSearchableAttributes($indexerId, 'index'));
            } catch (\Exception $exception) {
                return $this;
            }

            foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
                $batchDocuments = $this->attributeMapper->map($indexerId, $batchDocuments, $storeId);
                try {
                    $this->documentsManager->addDocumentsInBatches($indexName, $batchDocuments, $this->indexPrimaryKey);
                } catch (\Exception $e) {
                    return $this;
                }
            }
        }

        return $this;
    }

    /**
     * @param $dimensions
     * @param \Traversable $documents
     * @return IndexerInterface
     * @throws \Exception
     */
    public function deleteIndex($dimensions, \Traversable $documents): IndexerInterface
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            try {
                $this->indexesManager->deleteIndex($indexName);
            } catch (\Exception $exception) {
                return $this;
            }
        }

        return $this;
    }

    /**
     * @param $dimensions
     * @return IndexerInterface
     * @throws \Exception
     */
    public function cleanIndex($dimensions): IndexerInterface
    {
        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            try {
                $this->indexesManager->deleteIndex($indexName);
                $this->indexesManager->createIndex($indexName, $this->indexPrimaryKey);
            } catch (\Exception $exception) {
                return $this;
            }
        }

        return $this;
    }

    /**
     * @param Dimension[] $dimensions
     * @return bool
     * @throws \Exception
     */
    public function isAvailable($dimensions = []): bool
    {
        if ($this->engineResolver->getCurrentSearchEngine() !== Engine::SEARCH_ENGINE) {
            return false;
        }

        try {
            return $this->healthManager->isHealthy();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getIndexerId(): string
    {
        return $this->searchIndexNameResolver->getIndexMapping($this->indexerId);
    }
}
