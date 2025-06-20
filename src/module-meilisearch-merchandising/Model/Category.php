<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model;

use Magento\Framework\Model\AbstractModel;
use Walkwizus\MeilisearchMerchandising\Api\Data\CategoryInterface;

class Category extends AbstractModel implements CategoryInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Category::class);
    }

    /**
     * @ingeritdoc
     */
    public function getCategoryId(): mixed
    {
        return $this->getData(self::CATEGORY_ID);
    }

    /**
     * @ingeritdoc
     */
    public function setCategoryId($categoryId)
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    /**
     * @ingeritdoc
     */
    public function getQuery()
    {
        return $this->getData(self::QUERY);
    }

    /**
     * @ingeritdoc
     */
    public function setQuery(string $query)
    {
        return $this->setData(self::QUERY, $query);
    }

    /**
     * @ingeritdoc
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @ingeritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }
}
