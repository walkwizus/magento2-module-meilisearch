<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Api;

interface IndexerPreProcessorInterface
{
    public function prepare(string $indexerId, array $documentIds, int $storeId);
}
