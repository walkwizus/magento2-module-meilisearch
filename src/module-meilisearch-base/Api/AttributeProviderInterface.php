<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Api;

interface AttributeProviderInterface
{
    /**
     * @return array
     */
    public function getFilterableAttributes(): array;

    /**
     * @return array
     */
    public function getSearchableAttributes(): array;

    /**
     * @return array
     */
    public function getSortableAttributes(): array;
}
