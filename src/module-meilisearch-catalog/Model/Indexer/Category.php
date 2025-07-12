<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\Indexer;

use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Walkwizus\MeilisearchCatalog\Model\ResourceModel\Indexer\Category\Action\Full;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Search\Request\DimensionFactory;

class Category implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    const INDEXER_ID = 'meilisearch_categories_fulltext';

    /**
     * @param IndexerInterface $indexerHandler
     * @param Full $fullAction
     * @param StoreManagerInterface $storeManager
     * @param DimensionFactory $dimensionFactory
     */
    public function __construct(
        private readonly IndexerInterface $indexerHandler,
        private readonly Full $fullAction,
        private readonly StoreManagerInterface $storeManager,
        private readonly DimensionFactory $dimensionFactory
    ) { }

    /**
     * @param $ids
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($ids)
    {
        $storeIds = array_keys($this->storeManager->getStores());

        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->deleteIndex([$dimension], new \ArrayObject($ids));
            $this->indexerHandler->saveIndex([$dimension], new \ArrayObject($this->fullAction->getCategories($storeId, $ids)));
        }
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeFull()
    {
        $this->execute([]);
    }

    /**
     * @param array $ids
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * @param $id
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
