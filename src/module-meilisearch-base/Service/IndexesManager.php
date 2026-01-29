<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Meilisearch\Contracts\IndexesResults;
use Meilisearch\Contracts\Task;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Meilisearch\Exceptions\ApiException;

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
     * @return Task
     * @throws \Exception
     */
    public function createIndex($indexName, $primaryKey): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->createIndex($indexName, ['primaryKey' => $primaryKey]);
    }

    /**
     * @param $indexName
     * @param $primaryKey
     * @return Task
     * @throws \Exception
     */
    public function updateIndex($indexName, $primaryKey): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->updateIndex($indexName, ['primaryKey' => $primaryKey]);
    }

    /**
     * @param $indexName
     * @return Task
     * @throws \Exception
     */
    public function deleteIndex($indexName): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->deleteIndex($indexName);
    }

    /**
     * @param array $swaps
     * @return Task
     * @throws \Exception
     */
    public function swapIndexes(array $swaps): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->swapIndexes($swaps);
    }

    /**
     * @param string $indexName
     * @return bool
     * @throws ApiException
     */
    public function indexExists(string $indexName): bool
    {
        try {
            $client = $this->connectionManager->getConnection();
            $client->index($indexName)->fetchRawInfo();
            return true;
        } catch (ApiException $e) {
            if ($e->httpStatus === 404) {
                return false;
            }

            throw $e;
        }
    }
}
