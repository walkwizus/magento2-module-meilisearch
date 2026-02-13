<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;

class AttributeMapper
{
    /**
     * @param array $mappers
     * @param array $postMappers
     */
    public function __construct(
        private readonly array $mappers = [],
        private readonly array $postMappers = []
    ) { }

    /**
     * @param string $indexerId
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     */
    public function map(string $indexerId, array $documentData, $storeId, array $context = []): array
    {
        $mergedDocuments = [];
        $mappers = $this->resolve($indexerId, $this->mappers);

        if (count($mappers) > 0) {
            foreach ($mappers as $mapper) {
                if (!$mapper instanceof AttributeMapperInterface) {
                    throw new \LogicException('Attribute provider must implement "Walkwizus\MeilisearchBase\Api\AttributeMapperInterface".');
                }
                $data = $mapper->map($documentData, $storeId, $context);
                foreach ($data as $key => $value) {
                    if (!isset($mergedDocuments[$key])) {
                        $mergedDocuments[$key] = [];
                    }
                    $mergedDocuments[$key] = array_merge($mergedDocuments[$key], $value);
                }
            }
        } else {
            $mergedDocuments = $documentData;
        }

        $postMappers = $this->resolve($indexerId, $this->postMappers);

        foreach ($postMappers as $postMapper) {
            if (!$postMapper instanceof AttributeMapperInterface) {
                throw new \LogicException('Post-attribute mapper must implement "Walkwizus\MeilisearchBase\Api\AttributeMapperInterface".');
            }
            $mergedDocuments = $postMapper->map($mergedDocuments, $storeId);
        }

        return $mergedDocuments;
    }

    /**
     * @param $indexerId
     * @param array $source
     * @return AttributeMapperInterface|array
     */
    private function resolve($indexerId, array $source): AttributeMapperInterface|array
    {
        return $source[$indexerId] ?? [];
    }
}
