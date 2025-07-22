<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;

class SaveProductPosition
{
    /**
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param DocumentsManager $documentsManager
     */
    public function __construct(
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly DocumentsManager $documentsManager
    ) { }

    /**
     * @param array $positions
     * @param $categoryId
     * @param $storeId
     * @return void
     * @throws \Exception
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

        $this->documentsManager->updateDocuments($indexName, $updateDocuments);
    }
}
