<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Indexer;

use Walkwizus\MeilisearchBase\Api\IndexLifecycleInterface;

class IndexLifecyclePool implements IndexLifecycleInterface
{
    /**
     * @param IndexLifecycleInterface[] $lifecycles
     */
    public function __construct(
        private readonly array $lifecycles = []
    ) {
    }

    /**
     * @param string $indexUid
     * @param string $targetIndexUid
     * @return void
     */
    public function prepareIndex(string $indexUid, string $targetIndexUid): void
    {
        foreach ($this->lifecycles as $lifecycle) {
            $lifecycle->prepareIndex($indexUid, $targetIndexUid);
        }
    }

    /**
     * @param string $indexUid
     * @return bool
     */
    public function requiresInPlaceReindex(string $indexUid): bool
    {
        foreach ($this->lifecycles as $lifecycle) {
            if ($lifecycle->requiresInPlaceReindex($indexUid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $temporaryIndexUid
     * @param string $indexUid
     * @return void
     */
    public function afterIndexSwap(string $temporaryIndexUid, string $indexUid): void
    {
        foreach ($this->lifecycles as $lifecycle) {
            $lifecycle->afterIndexSwap($temporaryIndexUid, $indexUid);
        }
    }
}
