<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;

class AttributeMapper
{
    /**
     * @param array $mappers
     */
    public function __construct(
        private readonly array $mappers = []
    ) { }

    /**
     * @param string $indexerId
     * @param array $documentData
     * @param $storeId
     * @return array
     */
    public function map(string $indexerId, array $documentData, $storeId): array
    {
        $mergedDocuments = [];
        $mappers = $this->resolve($indexerId);

        if (count($mappers) == 0) {
            return $documentData;
        }

        foreach ($mappers as $mapper) {
            if (!$mapper instanceof AttributeMapperInterface) {
                throw new \LogicException('Attribute provider must implement "Walkwizus\MeilisearchBase\Api\AttributeMapperInterface".');
            }
            $data = $mapper->map($documentData, $storeId);
            foreach ($data as $key => $value) {
                if (!isset($mergedDocuments[$key])) {
                    $mergedDocuments[$key] = [];
                }
                $mergedDocuments[$key] = array_merge($mergedDocuments[$key], $value);
            }
        }

        return $mergedDocuments;
    }

    /**
     * @param $indexerId
     * @return AttributeMapperInterface|array
     */
    private function resolve($indexerId): AttributeMapperInterface|array
    {
        return $this->mappers[$indexerId] ?? [];
    }
}
