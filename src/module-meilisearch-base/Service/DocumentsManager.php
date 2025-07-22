<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

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
     * @param array $documents
     * @param string $primaryKey
     * @return array
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
     * @return mixed
     * @throws \Exception
     */
    public function addDocuments($indexName, array $documents, string $primaryKey): mixed
    {
        $client =  $this->connectionManager->getConnection();
        return $client->index($indexName)->addDocuments($documents);
    }

    /**
     * @param $indexName
     * @param array $documents
     * @return array
     * @throws \Exception
     */
    public function updateDocuments($indexName, array $documents): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateDocuments($documents);
    }
}
