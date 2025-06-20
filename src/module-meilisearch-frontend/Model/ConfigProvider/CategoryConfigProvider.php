<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Service\GetCategoryConfig;

class CategoryConfigProvider implements ConfigProviderInterface
{
    /**
     * @param GetCategoryConfig $categoryConfig
     */
    public function __construct(
        private readonly GetCategoryConfig $categoryConfig
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->categoryConfig->get();
    }
}
