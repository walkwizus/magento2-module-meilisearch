<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Api\Data;

interface LinkInterface
{
    public const INDEX_UID = 'index_uid';
    public const EMBEDDER_ID = 'embedder_id';

    /**
     * @return string
     */
    public function getIndexUid(): string;

    /**
     * @param string $uid
     * @return $this
     */
    public function setIndexUid(string $uid): static;

    /**
     * @return int
     */
    public function getEmbedderId(): int;

    /**
     * @param int $id
     * @return $this
     */
    public function setEmbedderId(int $id): static;
}
