<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Walkwizus\MeilisearchAi\Model\Link;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Link::class, \Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link::class);
    }
}
