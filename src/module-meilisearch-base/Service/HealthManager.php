<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Service;

use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

class HealthManager
{
    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isHealthy(): bool
    {
        $client = $this->connectionManager->getConnection();
        return $client->isHealthy();
    }
}
