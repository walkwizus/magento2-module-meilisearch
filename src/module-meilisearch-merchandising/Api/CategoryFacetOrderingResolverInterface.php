<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Api;

interface CategoryFacetOrderingResolverInterface
{
    /**
     * @param int $storeId
     * @param int $categoryId
     * @return array|null
     */
    public function resolve(int $storeId, int $categoryId): ?array;
}
