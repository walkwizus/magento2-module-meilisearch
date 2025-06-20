<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Api\Data;

interface CategoryInterface
{
    const CATEGORY_ID = 'category_id';

    const QUERY = 'query';

    const STORE_ID = 'store_id';

    /**
     * @return mixed
     */
    public function getCategoryId();

    /**
     * @param mixed $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId);

    /**
     * @return string
     */
    public function getQuery();

    /**
     * @param string $query
     * @return $this
     */
    public function setQuery(string $query);

    /**
     * @return mixed
     */
    public function getStoreId();

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId);
}
