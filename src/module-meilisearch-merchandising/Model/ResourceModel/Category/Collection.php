<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Walkwizus\MeilisearchMerchandising\Model\Category;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Category::class, \Walkwizus\MeilisearchMerchandising\Model\ResourceModel\Category::class);
    }
}
