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
        try {
            $client = $this->connectionManager->getConnection();
        } catch (\Exception $e) {
            return false;
        }

        return $client->isHealthy();
    }
}
