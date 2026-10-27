<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Api;

interface EmbedderConfigProviderInterface
{
    /**
     * @param string $indexUid
     * @return array Embedder settings for Meilisearch, keyed by embedder identifier
     */
    public function getEmbedders(string $indexUid): array;
}
