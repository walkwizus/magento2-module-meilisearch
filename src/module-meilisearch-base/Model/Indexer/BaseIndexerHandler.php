<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Search\Model\EngineResolver;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchBase\Service\IndexesManager;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;
use Walkwizus\MeilisearchBase\Service\HealthManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Walkwizus\MeilisearchBase\Model\AttributeMapper;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchAi\Model\EmbedderSynchronizer;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;

class BaseIndexerHandler implements IndexerInterface
{
    const INDEX_SWAP_SUFFIX = 'tmp';
    const REINDEX_RUN_ATTRIBUTE = 'meilisearch_reindex_run';

    /**
     * @var bool
     */
    private bool $isFullReindex = false;

    /**
     * @var array
     */
    private array $temporaryIndexes = [];

    /**
     * @var array
     */
    private array $inPlaceRuns = [];

    /**
     * @param EngineResolver $engineResolver
     * @param SettingsManager $settingsManager
     * @param IndexesManager $indexesManager
     * @param DocumentsManager $documentsManager
     * @param HealthManager $healthManager
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param Batch $batch
     * @param string $indexerId
     * @param AttributeMapper $attributeMapper
     * @param AttributeProvider $attributeProvider
     * @param EmbedderSynchronizer $embedderSynchronizer
     * @param LoggerInterface $logger
     * @param int $batchSize
     * @param string $indexPrimaryKey
     * @param array $preProcessors
     */
    public function __construct(
        private readonly EngineResolver $engineResolver,
        private readonly SettingsManager $settingsManager,
        private readonly IndexesManager $indexesManager,
        private readonly DocumentsManager $documentsManager,
        private readonly HealthManager $healthManager,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly Batch $batch,
        private readonly string $indexerId,
        private readonly AttributeMapper $attributeMapper,
        private readonly AttributeProvider $attributeProvider,
        private readonly EmbedderSynchronizer $embedderSynchronizer,
        private readonly LoggerInterface $logger,
        private readonly int $batchSize = 10000,
        private readonly string $indexPrimaryKey = 'id',
        private readonly array $preProcessors = []
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
            $storeId = (int)$dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);
            $runId = $this->inPlaceRuns[$indexName] ?? null;
            $targetIndexName = $this->isFullReindex && $runId === null ? $indexName . '_' . self::INDEX_SWAP_SUFFIX : $indexName;
            $filterableAttributes = $this->attributeProvider->getFilterableAttributes($indexerId, 'index');

            if ($runId !== null) {
                $filterableAttributes[] = self::REINDEX_RUN_ATTRIBUTE;
            }

            try {
                $this->settingsManager->updateFilterableAttributes($targetIndexName, $filterableAttributes);
                $this->settingsManager->updateSortableAttributes($targetIndexName, $this->attributeProvider->getSortableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSearchableAttributes($targetIndexName, $this->attributeProvider->getSearchableAttributes($indexerId, 'index'));
                $this->embedderSynchronizer->sync($indexName, $targetIndexName);
            } catch (\Exception $e) {
                throw $this->failure('Unable to prepare the settings of Meilisearch index %1: %2', $targetIndexName, $e);
            }

            foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
                $context = [];

                if (isset($this->preProcessors[$indexerId])) {
                    $context = $this->preProcessors[$indexerId]->prepare($indexerId, $batchDocuments, $storeId);
                }

                $batchDocuments = $this->attributeMapper->map($indexerId, $batchDocuments, $storeId, $context);

                if ($runId !== null) {
                    foreach ($batchDocuments as $key => $document) {
                        $batchDocuments[$key][self::REINDEX_RUN_ATTRIBUTE] = $runId;
                    }
                }

                try {
                    $this->documentsManager->addDocumentsInBatches($targetIndexName, $batchDocuments, $this->indexPrimaryKey);
                } catch (\Exception $e) {
                    throw $this->failure('Unable to send documents to Meilisearch index %1: %2', $targetIndexName, $e);
                }
            }

            if ($runId !== null) {
                $this->deleteStaleDocuments($indexName, $runId);
            }
        }

        if ($this->isFullReindex && !empty($this->temporaryIndexes)) {
            $this->performSwap();
        }

        if ($this->temporaryIndexes === [] && $this->inPlaceRuns === []) {
            $this->isFullReindex = false;
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

            if (!$this->indexesManager->indexExists($indexName)) {
                continue;
            }

            $this->documentsManager->deleteDocuments($indexName, (array)$documents);
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
        $this->isFullReindex = true;

        foreach ($dimensions as $dimension) {
            $storeId = $dimension->getValue();
            $indexerId = $this->getIndexerId();
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);
            $tmpIndexName = $indexName . '_' . self::INDEX_SWAP_SUFFIX;

            if ($this->embedderSynchronizer->hasEmbedders($indexName)) {
                $this->inPlaceRuns[$indexName] = bin2hex(random_bytes(8));
            } else {
                $this->temporaryIndexes[$indexName] = $tmpIndexName;
            }

            if (!$this->indexesManager->indexExists($indexName)) {
                $this->indexesManager->createIndex($indexName, $this->indexPrimaryKey);
            }

            if ($this->indexesManager->indexExists($tmpIndexName)) {
                $this->indexesManager->deleteIndex($tmpIndexName);
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

    /**
     * @return void
     */
    private function performSwap(): void
    {
        $swaps = [];
        foreach ($this->temporaryIndexes as $realIndex => $tmpIndex) {
            $swaps[] = [$realIndex, $tmpIndex];
        }

        try {
            $this->indexesManager->swapIndexes($swaps);

            foreach ($this->temporaryIndexes as $realIndex => $tmpIndex) {
                $this->indexesManager->deleteIndex($tmpIndex);
                $this->embedderSynchronizer->moveState($tmpIndex, $realIndex);
            }
        } catch (\Exception $e) {
            throw $this->failure('Unable to swap Meilisearch indexes %1: %2', implode(', ', array_keys($this->temporaryIndexes)), $e);
        } finally {
            $this->isFullReindex = false;
            $this->temporaryIndexes = [];
        }
    }

    /**
     * @param string $indexName
     * @param string $runId
     * @return void
     * @throws LocalizedException
     */
    private function deleteStaleDocuments(string $indexName, string $runId): void
    {
        try {
            $this->documentsManager->deleteDocumentsByFilter(
                $indexName,
                self::REINDEX_RUN_ATTRIBUTE . ' != "' . $runId . '"'
            );
        } catch (\Exception $e) {
            throw $this->failure('Unable to delete stale documents from Meilisearch index %1: %2', $indexName, $e);
        } finally {
            unset($this->inPlaceRuns[$indexName]);
        }
    }

    /**
     * @param string $message
     * @param string $indexName
     * @param \Exception $exception
     * @return LocalizedException
     */
    private function failure(string $message, string $indexName, \Exception $exception): LocalizedException
    {
        $this->logger->error(
            str_replace(['%1', '%2'], [$indexName, $exception->getMessage()], $message),
            ['exception' => $exception]
        );

        return new LocalizedException(__($message, $indexName, $exception->getMessage()), $exception);
    }
}
