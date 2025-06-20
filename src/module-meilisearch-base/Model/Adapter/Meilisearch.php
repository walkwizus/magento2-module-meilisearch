<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Adapter;

use Meilisearch\Client;
use Meilisearch\Search\SearchResult;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class Meilisearch
{
    /**
     * @var Client|null
     */
    private ?Client $client;

    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(ConnectionManager $connectionManager)
    {
        try {
            $this->client = $connectionManager->getConnection();
        } catch (\Exception $e) {

        }
    }

    /**
     * @param string $indexName
     * @param array $documents
     * @param string $primaryKey
     * @return $this
     */
    public function addDocs(string $indexName, array $documents, string $primaryKey): static
    {
        if (count($documents)) {
            $this->client->updateIndex($indexName, ['primaryKey' => $primaryKey]);
            $index = $this->client->index($indexName);
            $index->addDocumentsInBatches($documents, count($documents), $primaryKey);
        }

        return $this;
    }

    /**
     * @param string $indexName
     * @param array $documents
     * @return $this
     */
    public function updateDocuments(string $indexName, array $documents): static
    {
        if (count($documents)) {
            try {
                $this->client->index($indexName)->updateDocuments($documents);
            } catch (\Exception $e) {

            }
        }
        return $this;
    }

    /**
     * @param $index
     * @param string $query
     * @param array $params
     * @return SearchResult
     */
    public function search($index, string $query = '', array $params = []): SearchResult
    {
        return $this->client->index($index)->search($query, $params);
    }

    /**
     * @param $indexName
     * @param array $settings
     * @return $this
     */
    public function updateSettings($indexName, array $settings): static
    {
        if (count($settings) > 0) {
            $this->client->index($indexName)->updateSettings($settings);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->client->isHealthy();
    }
}
