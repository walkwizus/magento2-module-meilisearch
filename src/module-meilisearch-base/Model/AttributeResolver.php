<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model;

class AttributeResolver
{
    /**
     * @param array $resolvers
     */
    public function __construct(
        private readonly array $resolvers
    ) { }

    /**
     * @param string $attribute
     * @return string
     */
    public function resolve(string $attribute): string
    {
        if (isset($this->resolvers[$attribute])) {
            return $this->resolvers[$attribute]->resolve();
        }

        return $attribute;
    }
}
