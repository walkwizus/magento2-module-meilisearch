<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Api;

interface AttributeMapperInterface
{
    /**
     * @param array $documentData
     * @param $storeId
     * @return array
     */
    public function map(array $documentData, $storeId): array;
}
