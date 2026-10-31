<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Api;

interface IndexLifecycleInterface
{
    /**
     * @param string $indexUid
     * @param string $targetIndexUid
     * @return void
     */
    public function prepareIndex(string $indexUid, string $targetIndexUid): void;

    /**
     * @param string $indexUid
     * @return bool
     */
    public function requiresInPlaceReindex(string $indexUid): bool;

    /**
     * @param string $temporaryIndexUid
     * @param string $indexUid
     * @return void
     */
    public function afterIndexSwap(string $temporaryIndexUid, string $indexUid): void;
}
