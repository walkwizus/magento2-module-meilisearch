<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\Model\AbstractModel;
use Walkwizus\MeilisearchAi\Api\Data\LinkInterface;

class Link extends AbstractModel implements LinkInterface
{
    /**
     * @return string
     */
    public function getIndexUid(): string
    {
        return $this->getData(self::INDEX_UID);
    }

    /**
     * @param string $uid
     * @return $this
     */
    public function setIndexUid(string $uid): static
    {
        return $this->setData(self::INDEX_UID, $uid);
    }

    /**
     * @return int
     */
    public function getEmbedderId(): int
    {
        return $this->getData(self::EMBEDDER_ID);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setEmbedderId(int $id): static
    {
        return $this->setData(self::EMBEDDER_ID, $id);
    }
}
