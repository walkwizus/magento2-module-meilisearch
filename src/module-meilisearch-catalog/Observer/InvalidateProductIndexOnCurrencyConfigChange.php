<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Observer;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

class InvalidateProductIndexOnCurrencyConfigChange implements ObserverInterface
{
    private const CURRENCY_OPTIONS_PATH = 'currency/options/';

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry
    ) { }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $changedPaths = (array) $observer->getEvent()->getData('changed_paths');

        foreach ($changedPaths as $path) {
            if (str_starts_with((string) $path, self::CURRENCY_OPTIONS_PATH)) {
                $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();

                return;
            }
        }
    }
}
