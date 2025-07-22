<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Meilisearch\Contracts\KeysResults;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class KeysManager
{
    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @return KeysResults
     * @throws \Exception
     */
    public function getKeys(): KeysResults
    {
        $client = $this->connectionManager->getConnection(true);
        return $client->getKeys();
    }
}
