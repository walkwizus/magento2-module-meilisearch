<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Category extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('meilisearch_merchandising_category', 'id');
    }
}
