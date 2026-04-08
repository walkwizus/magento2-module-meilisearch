<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\StateItemRenderer;

use Magento\Framework\View\Element\Template;

class CheckboxRenderer extends AbstractStateItemRenderer
{
    /**
     * @param array<string, mixed> $facet
     * @param array<string, mixed> $params
     */
    public function render(Template $block, array $facet, array $params): string
    {
        $facetCode = $this->getFacetCode($facet);
        if ($facetCode === '') {
            return '';
        }

        $selectedValue = $this->getSelectedValue($facetCode, $params);
        if ($selectedValue === '') {
            return '';
        }

        return $this->renderItemHtml(
            $block,
            $this->getFacetLabel($facet),
            $this->normalizeDisplayValue($selectedValue),
            $this->buildRemoveUrl($block, $facetCode, $params)
        );
    }
}

