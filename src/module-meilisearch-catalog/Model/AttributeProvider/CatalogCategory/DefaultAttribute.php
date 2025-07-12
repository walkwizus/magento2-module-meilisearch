<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeProvider\CatalogCategory;

use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;

class DefaultAttribute implements AttributeProviderInterface
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
        return ['name'];
    }

    /**
     * @return array
     */
    public function getSortableAttributes(): array
    {
        return [];
    }
}
