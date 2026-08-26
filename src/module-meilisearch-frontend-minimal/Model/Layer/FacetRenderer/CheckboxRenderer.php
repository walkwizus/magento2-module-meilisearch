<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\FacetRenderer;

use Magento\Framework\View\Element\Template;

class CheckboxRenderer extends AbstractFacetRenderer
{
    /**
     * @param array<string, mixed> $facet
     * @param array<string, int|string> $options
     * @param array<string, mixed> $params
     */
    public function render(Template $block, array $facet, array $options, array $params): string
    {
        $facetCode = $this->getFacetCode($facet);
        if ($facetCode === '' || $options === []) {
            return '';
        }

        $selected = $this->getSelectedValue($facetCode, $params);
        $html = '';

        foreach ($options as $value => $count) {
            $valueString = (string)$value;
            $url = $this->buildFilterUrl($block, $facetCode, $valueString, $params);
            $activeClass = $selected === $valueString ? ' active' : '';

            $html .= '<div class="filter-option">';
            $html .= '<a href="' . $block->escapeUrl($url) . '" class="' . trim($activeClass) . '">';
            $html .= $block->escapeHtml($valueString) . ' (' . (int)$count . ')';
            $html .= '</a>';
            $html .= '</div>';
        }

        return $html;
    }
}

