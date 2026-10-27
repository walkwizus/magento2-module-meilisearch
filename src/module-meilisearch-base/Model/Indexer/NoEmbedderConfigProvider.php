<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Indexer;

use Walkwizus\MeilisearchBase\Api\EmbedderConfigProviderInterface;

class NoEmbedderConfigProvider implements EmbedderConfigProviderInterface
{
    /**
     * Used when Walkwizus_MeilisearchAi is disabled: indexes get no embedders.
     *
     * @param string $indexUid
     * @return array
     */
    public function getEmbedders(string $indexUid): array
    {
        return [];
    }
}
