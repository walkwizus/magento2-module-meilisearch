<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Walkwizus\MeilisearchAi\Model\Embedder;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Embedder::class, \Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder::class);
    }
}
