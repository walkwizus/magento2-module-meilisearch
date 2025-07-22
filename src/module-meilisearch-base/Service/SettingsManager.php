<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

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
    public function getSettings($indexName)
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->getSettings();
    }

    /**
     * @param $indexName
     * @param array $settings
     * @return array
     * @throws \Exception
     */
    public function updateSettings($indexName, array $settings): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSettings($settings);
    }

    /**
     * @param $indexName
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    public function updateFilterableAttributes($indexName, array $attributes): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateFilterableAttributes($attributes);
    }

    /**
     * @param $indexName
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    public function updateSearchableAttributes($indexName, array $attributes): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSearchableAttributes($attributes);
    }

    /**
     * @param $indexName
     * @param array $attributes
     * @return array
     * @throws \Exception
     */
    public function updateSortableAttributes($indexName, array $attributes): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSortableAttributes($attributes);
    }

    /**
     * @param $indexName
     * @param array $rules
     * @return array
     * @throws \Exception
     */
    public function updateRankingRules($indexName, array $rules): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateRankingRules($rules);
    }

    /**
     * @param $indexName
     * @param array $stopWords
     * @return array
     * @throws \Exception
     */
    public function updateStopWords($indexName, array $stopWords): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateStopWords($stopWords);
    }

    /**
     * @param $indexName
     * @param array $synonyms
     * @return array
     * @throws \Exception
     */
    public function updateSynonyms($indexName, array $synonyms): array
    {
        $client = $this->connectionManager->getConnection();
        return $client->index($indexName)->updateSynonyms($synonyms);
    }
}
