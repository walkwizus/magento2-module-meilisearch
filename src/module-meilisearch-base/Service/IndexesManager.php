<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Meilisearch\Contracts\IndexesResults;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class IndexesManager
{
    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @return IndexesResults
     * @throws \Exception
     */
    public function getIndexes(): IndexesResults
    {
        $client = $this->connectionManager->getConnection();
        return $client->getIndexes();
    }

    /**
     * @param $indexName
     * @param $primaryKey
     * @return array
     * @throws \Exception
     */
    public function createIndex($indexName, $primaryKey): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->createIndex($indexName, ['primaryKey' => $primaryKey]);
    }

    /**
     * @param $indexName
     * @param $primaryKey
     * @return array
     * @throws \Exception
     */
    public function updateIndex($indexName, $primaryKey): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->updateIndex($indexName, ['primaryKey' => $primaryKey]);
    }

    /**
     * @param $indexName
     * @return array
     * @throws \Exception
     */
    public function deleteIndex($indexName): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->deleteIndex($indexName);
    }
}
