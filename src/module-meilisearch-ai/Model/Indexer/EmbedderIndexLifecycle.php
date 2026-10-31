<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\Indexer;

use Walkwizus\MeilisearchAi\Model\EmbedderSynchronizer;
use Walkwizus\MeilisearchBase\Api\IndexLifecycleInterface;

class EmbedderIndexLifecycle implements IndexLifecycleInterface
{
    /**
     * @param EmbedderSynchronizer $embedderSynchronizer
     */
    public function __construct(
        private readonly EmbedderSynchronizer $embedderSynchronizer
    ) {
    }

    /**
     * @param string $indexUid
     * @param string $targetIndexUid
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Meilisearch\Exceptions\ApiException
     */
    public function prepareIndex(string $indexUid, string $targetIndexUid): void
    {
        $this->embedderSynchronizer->sync($indexUid, $targetIndexUid);
    }

    /**
     * @param string $indexUid
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function requiresInPlaceReindex(string $indexUid): bool
    {
        return $this->embedderSynchronizer->hasEmbedders($indexUid);
    }

    /**
     * @param string $temporaryIndexUid
     * @param string $indexUid
     * @return void
     */
    public function afterIndexSwap(string $temporaryIndexUid, string $indexUid): void
    {
        $this->embedderSynchronizer->moveState($temporaryIndexUid, $indexUid);
    }
}
