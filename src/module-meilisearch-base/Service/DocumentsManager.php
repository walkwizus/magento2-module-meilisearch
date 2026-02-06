<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Meilisearch\Contracts\Task;

class DocumentsManager
{
    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @param $indexName
     * @param $id
     * @param array|null $fields
     * @return array
     * @throws \Exception
     */
    public function getDocument($indexName, $id, ?array $fields = null): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->getDocument($id, $fields);
    }

    /**
     * @param $indexName
     * @param array $documents
     * @param string $primaryKey
     * @return Task[]
     * @throws \Exception
     */
    public function addDocumentsInBatches($indexName, array $documents, string $primaryKey): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->addDocumentsInBatches($documents, count($documents), $primaryKey);
    }

    /**
     * @param $indexName
     * @param array $documents
     * @param string $primaryKey
     * @return Task
     * @throws \Exception
     */
    public function addDocuments($indexName, array $documents, string $primaryKey): Task
    {
        $client =  $this->connectionManager->getConnection();
        return $client->index($indexName)->addDocuments($documents);
    }

    /**
     * @param $indexName
     * @param array $documents
     * @return Task
     * @throws \Exception
     */
    public function updateDocuments($indexName, array $documents): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateDocuments($documents);
    }

    /**
     * @param $indexName
     * @param array $documentIds
     * @return Task
     * @throws \Exception
     */
    public function deleteDocuments($indexName, array $documentIds): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->deleteDocuments($documentIds);
    }
}
