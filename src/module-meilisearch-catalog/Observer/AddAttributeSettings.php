<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\EngineResolver;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;

class AddAttributeSettings implements ObserverInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param EngineResolver $engineResolver
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param SettingsManager $settingsManager
     * @param AttributeProvider $attributeProvider
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly EngineResolver $engineResolver,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly SettingsManager $settingsManager,
        private readonly AttributeProvider $attributeProvider
    ) { }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if ($this->engineResolver->getCurrentSearchEngine() !== Engine::SEARCH_ENGINE) {
            return $this;
        }

        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $indexerId = SearchIndexNameResolver::DEFAULT_INDEX;
            $indexName = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);

            try {
                $this->settingsManager->updateFilterableAttributes($indexName, $this->attributeProvider->getFilterableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSortableAttributes($indexName, $this->attributeProvider->getSortableAttributes($indexerId, 'index'));
                $this->settingsManager->updateSearchableAttributes($indexName, $this->attributeProvider->getSearchableAttributes($indexerId, 'index'));
            } catch (\Exception $e) {

            }
        }

        return $this;
    }
}
