<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributeProvider\CatalogProduct\Index;

use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;

class CategoryPromote implements AttributeProviderInterface
{
    /**
     * @return array
     */
    public function getFilterableAttributes(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getSearchableAttributes(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getSortableAttributes(): array
    {
        return ['category_promote'];
    }
}
