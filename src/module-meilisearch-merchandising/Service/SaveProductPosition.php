<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Walkwizus\MeilisearchBase\Model\Adapter\Meilisearch;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;

class SaveProductPosition
{
    /**
     * @param Meilisearch $meilisearchAdapter
     * @param SearchIndexNameResolver $searchIndexNameResolver
     */
    public function __construct(
        private readonly Meilisearch $meilisearchAdapter,
        private readonly SearchIndexNameResolver $searchIndexNameResolver
    ) { }

    /**
     * @param array $positions
     * @param $categoryId
     * @param $storeId
     * @return void
     */
    public function execute(array $positions, $categoryId, $storeId): void
    {
        $indexName = $this->searchIndexNameResolver->getIndexName($storeId, 'catalog_product');
        $updateDocuments = array_map(function ($item) use ($categoryId) {
            return [
                'id' => $item['id'],
                'position_category_' . $categoryId => $item['position']
            ];
        }, $positions);

        $this->meilisearchAdapter->updateDocuments($indexName, $updateDocuments);
    }
}
