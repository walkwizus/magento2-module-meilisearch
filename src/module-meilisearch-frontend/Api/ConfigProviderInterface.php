<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Api;

interface ConfigProviderInterface
{
    /**
     * @return array
     */
    public function get(): array;
}
