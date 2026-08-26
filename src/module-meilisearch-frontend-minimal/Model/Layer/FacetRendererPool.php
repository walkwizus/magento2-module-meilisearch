<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer;

use BA\MeilisearchFrontendMinimal\Api\Layer\FacetRendererInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;

class FacetRendererPool implements ArgumentInterface
{
    /**
     * @param array<string, FacetRendererInterface> $renderers
     */
    public function __construct(
        private readonly FacetRendererInterface $defaultRenderer,
        private readonly array $renderers = []
    ) {
    }

    /**
     * @param array<string, mixed> $facet
     * @param array<string, int|string> $options
     * @param array<string, mixed> $params
     */
    public function render(Template $block, array $facet, array $options, array $params): string
    {
        $rendererKey = $this->resolveRendererKey($facet);
        $renderer = $this->renderers[$rendererKey] ?? $this->defaultRenderer;

        return $renderer->render($block, $facet, $options, $params);
    }

    /**
     * @param array<string, mixed> $facet
     */
    private function resolveRendererKey(array $facet): string
    {
        $renderRegion = (string)($facet['renderRegion'] ?? '');
        if ($renderRegion !== '') {
            return $renderRegion;
        }

        $type = (string)($facet['type'] ?? '');
        if ($type !== '') {
            return $type;
        }

        return 'checkbox';
    }
}

