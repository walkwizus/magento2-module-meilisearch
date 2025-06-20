<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Api;

interface AttributeResolverInterface
{
    /**
     * @return string
     */
    public function resolve(): string;
}
