<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Meilisearch\Contracts\Task;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class SettingsManager
{
    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @param $indexName
     * @return array
     * @throws \Exception
     */
    public function getSettings($indexName): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->getSettings();
    }

    /**
     * @param $indexName
     * @param array $settings
     * @return Task
     * @throws \Exception
     */
    public function updateSettings($indexName, array $settings): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSettings($settings);
    }

    /**
     * @param $indexName
     * @param array $attributes
     * @return Task
     * @throws \Exception
     */
    public function updateFilterableAttributes($indexName, array $attributes): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateFilterableAttributes($attributes);
    }

    /**
     * @param $indexName
     * @param array $attributes
     * @return Task
     * @throws \Exception
     */
    public function updateSearchableAttributes($indexName, array $attributes): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSearchableAttributes($attributes);
    }

    /**
     * @param $indexName
     * @param array $attributes
     * @return Task
     * @throws \Exception
     */
    public function updateSortableAttributes($indexName, array $attributes): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSortableAttributes($attributes);
    }

    /**
     * @param $indexName
     * @param array $rules
     * @return Task
     * @throws \Exception
     */
    public function updateRankingRules($indexName, array $rules): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateRankingRules($rules);
    }

    /**
     * @param $indexName
     * @param array $stopWords
     * @return Task
     * @throws \Exception
     */
    public function updateStopWords($indexName, array $stopWords): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateStopWords($stopWords);
    }

    /**
     * @param $indexName
     * @param array $synonyms
     * @return Task
     * @throws \Exception
     */
    public function updateSynonyms($indexName, array $synonyms): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSynonyms($synonyms);
    }

    /**
     * @param $indexName
     * @param array $typoTolerance
     * @return Task
     * @throws \Exception
     */
    public function updateTypoTolerance($indexName, array $typoTolerance): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateTypoTolerance($typoTolerance);
    }

    /**
     * @param $indexName
     * @param array $embedders
     * @return Task
     * @throws \Exception
     */
    public function updateEmbedders($indexName, array $embedders): Task
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateEmbedders($embedders);
    }
}
