<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Api\Layer;

use Magento\Framework\View\Element\Template;

interface StateItemRendererInterface
{
    /**
     * @param array<string, mixed> $facet
     * @param array<string, mixed> $params
     */
    public function render(Template $block, array $facet, array $params): string;
}

