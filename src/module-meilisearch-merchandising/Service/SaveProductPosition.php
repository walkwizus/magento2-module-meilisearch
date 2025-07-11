<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Walkwizus\MeilisearchBase\Model\Adapter\MeilisearchFactory;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Model\Adapter\Meilisearch;

class SaveProductPosition
{
    /**
     * @var Meilisearch|null
     */
    private ?Meilisearch $meilisearchClient;

    /**
     * @param MeilisearchFactory $meilisearchFactory
     * @param SearchIndexNameResolver $searchIndexNameResolver
     */
    public function __construct(
        readonly MeilisearchFactory $meilisearchFactory,
        private readonly SearchIndexNameResolver $searchIndexNameResolver
    ) {
        $this->meilisearchClient = $meilisearchFactory->create();
    }

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

        $this->meilisearchClient->updateDocuments($indexName, $updateDocuments);
    }
}
