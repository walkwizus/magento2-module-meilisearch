<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Search\Model\EngineResolver;
use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchBase\Service\IndexesManager;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;
use Walkwizus\MeilisearchBase\Service\HealthManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Walkwizus\MeilisearchBase\Model\AttributeMapper;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as EmbedderLinkResource;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory as EmbedderCollectionFactory;
use Magento\Framework\Search\Request\Dimension;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;

class BaseIndexerHandler implements IndexerInterface
{
    const INDEX_SWAP_SUFFIX = 'tmp';

    /**
     * @var bool
     */
    private bool $isFullReindex = false;

    /**
     * @var array
     */
    private array $temporaryIndexes = [];

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
     * @param EmbedderLinkResource $embedderLinkResource
     * @param EmbedderCollectionFactory $embedderCollectionFactory
     * @param int $batchSize
     * @param string $indexPrimaryKey
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
        private readonly EmbedderLinkResource $embedderLinkResource,
        private readonly EmbedderCollectionFactory $embedderCollectionFactory,
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
            $targetIndexName = $this->isFullReindex ? $indexName . '_' . self::INDEX_SWAP_SUFFIX : $indexName;

            try {
                $this->settingsManager->updateFilterableAttributes($targetIndexName, $this->attributeProvider->getFilterableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSortableAttributes($targetIndexName, $this->attributeProvider->getSortableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSearchableAttributes($targetIndexName, $this->attributeProvider->getSearchableAttributes($indexerId, 'index'));

                $embedderIds = $this->embedderLinkResource->getEmbedderIdsByUid($indexName);
                $embeddersConfig = [];

                if (!empty($embedderIds)) {
                    $embedders = $this->embedderCollectionFactory
                        ->create()
                        ->addFieldToFilter('embedder_id', ['in' => $embedderIds]);

                    /** @var EmbedderInterface $embedder */
                    foreach ($embedders as $embedder) {
                        $embeddersConfig[$embedder->getIdentifier()] = [
                            'source' => $embedder->getSource(),
                            'model' => $embedder->getModel(),
                            'apiKey' => $embedder->getApiKey(),
                            'documentTemplate' => $embedder->getDocumentTemplate(),
                        ];
                    }
                }

                $this->settingsManager->updateEmbedders($targetIndexName, $embeddersConfig);

            } catch (\Exception $exception) {
                return $this;
            }

            foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
                $batchDocuments = $this->attributeMapper->map($indexerId, $batchDocuments, $storeId);
                try {
                    $this->documentsManager->addDocumentsInBatches($targetIndexName, $batchDocuments, $this->indexPrimaryKey);
                } catch (\Exception $e) {
                    return $this;
                }
            }
        }

        if ($this->isFullReindex && !empty($this->temporaryIndexes)) {
            $this->performSwap();
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

            $this->temporaryIndexes[$indexName] = $tmpIndexName;

            if (!$this->indexesManager->indexExists($indexName)) {
                $this->indexesManager->createIndex($indexName, $this->indexPrimaryKey);
            }

            $this->indexesManager->deleteIndex($tmpIndexName);
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

            foreach ($this->temporaryIndexes as $tmpIndex) {
                $this->indexesManager->deleteIndex($tmpIndex);
            }
        } catch (\Exception $e) {

        } finally {
            $this->isFullReindex = false;
            $this->temporaryIndexes = [];
        }
    }
}
