<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model;

use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;

class AttributeProvider
{
    /**
     * @param array $providers
     */
    public function __construct(
        private readonly array $providers = []
    ) { }

    /**
     * @param string $indexerId
     * @param string $context
     * @return array
     */
    public function getFilterableAttributes(string $indexerId, string $context): array
    {
        $providers = $this->resolve($indexerId, $context);
        return $this->getAttributes('filterable', $providers);
    }

    /**
     * @param string $indexerId
     * @param string $context
     * @return array
     */
    public function getSearchableAttributes(string $indexerId, string $context): array
    {
        $providers = $this->resolve($indexerId, $context);
        return $this->getAttributes('searchable', $providers);
    }

    /**
     * @param string $indexerId
     * @param string $context
     * @return array
     */
    public function getSortableAttributes(string $indexerId, string $context): array
    {
        $providers = $this->resolve($indexerId, $context);
        return $this->getAttributes('sortable', $providers);
    }

    /**
     * @param string $type
     * @param array $providers
     * @return array
     */
    private function getAttributes(string $type, array $providers): array
    {
        $attributes = [];
        foreach ($providers as $provider) {
            if (!$provider instanceof AttributeProviderInterface) {
                throw new \LogicException('Attribute provider must implement "Walkwizus\MeilisearchBase\Api\AttributeProviderInterface".');
            }
            $method = 'get' . ucfirst($type) . 'Attributes';
            $attributes += array_flip($provider->$method());
        }

        return array_keys($attributes);
    }

    /**
     * @param string $indexerId
     * @param string $context
     * @return array
     */
    private function resolve(string $indexerId, string $context): array
    {
        return $this->providers[$indexerId][$context] ?? [];
    }
}
