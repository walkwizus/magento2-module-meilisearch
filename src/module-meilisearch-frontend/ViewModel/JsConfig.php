<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

class JsConfig implements ArgumentInterface
{
    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        private readonly ConfigProvider $configProvider
    ) { }

    /**
     * @param string $componentName
     * @return array
     */
    public function get(string $componentName): array
    {
        return $this->configProvider->get($componentName);
    }
}
