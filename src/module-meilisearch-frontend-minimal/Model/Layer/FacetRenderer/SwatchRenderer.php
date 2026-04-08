<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\FacetRenderer;

use BA\MeilisearchFrontendMinimal\Block\Layer\SwatchRenderer as SwatchRendererBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Element\Template;

class SwatchRenderer extends AbstractFacetRenderer
{
    public function __construct(
        private readonly LayoutInterface $layout,
        private readonly CheckboxRenderer $fallbackRenderer
    ) {
    }

    /**
     * @param array<string, mixed> $facet
     * @param array<string, int|string> $options
     * @param array<string, mixed> $params
     */
    public function render(Template $block, array $facet, array $options, array $params): string
    {
        if ($options === []) {
            return '';
        }

        $rendererBlock = $this->layout->createBlock(SwatchRendererBlock::class);
        if (!$rendererBlock instanceof SwatchRendererBlock) {
            return $this->fallbackRenderer->render($block, $facet, $options, $params);
        }

        $rendererBlock->setTemplate('BA_MeilisearchFrontendMinimal::layer/filter/swatch.phtml');
        $rendererBlock->setData('facet', $facet);
        $rendererBlock->setData('options', $options);
        $rendererBlock->setData('params', $params);

        $html = $rendererBlock->toHtml();
        if (trim($html) === '') {
            return $this->fallbackRenderer->render($block, $facet, $options, $params);
        }

        return $html;
    }
}
