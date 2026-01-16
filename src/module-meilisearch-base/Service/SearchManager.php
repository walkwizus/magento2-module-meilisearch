<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Meilisearch\Search\SearchResult;
use Meilisearch\Contracts\MultiSearchFederation;

class SearchManager
{
    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @param $index
     * @param string $query
     * @param array $params
     * @return array|SearchResult
     * @throws \Exception
     */
    public function search($index, string $query = '', array $params = []): array|SearchResult
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($index)->search($query, $params);
    }

    /**
     * @param array $queries
     * @param MultiSearchFederation|null $federation
     * @return mixed
     * @throws \Exception
     */
    public function multisearch(array $queries = [], ?MultiSearchFederation $federation = null): mixed
    {
        $client = $this->connectionManager->getConnection();
        return $client->multisearch($queries);
    }
}
