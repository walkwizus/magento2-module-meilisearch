<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\SearchAdapter;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Walkwizus\MeilisearchBase\Helper\ServerSettings;

class SearchIndexNameResolver
{
    const DEFAULT_INDEX = 'catalog_product';

    /**
     * @param ServerSettings $settings
     */
    public function __construct(
        private readonly ServerSettings $settings
    ) { }

    /**
     * @param $storeId
     * @param string $indexerId
     * @return string
     */
    public function getIndexName($storeId, string $indexerId = self::DEFAULT_INDEX): string
    {
        $mappedIndexId = $this->getIndexMapping($indexerId);
        $prefix = $this->settings->getServerSettingsIndexesPrefix();

        if ($prefix != '') {
            $prefix = $prefix . '_';
        }

        return $prefix . $mappedIndexId . '_' . $storeId;
    }

    /**
     * @param string $indexerId
     * @return string
     */
    public function getIndexMapping(string $indexerId): string
    {
        return ($indexerId == Fulltext::INDEXER_ID) ? self::DEFAULT_INDEX : $indexerId;
    }
}
