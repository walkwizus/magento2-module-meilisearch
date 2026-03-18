<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\StateItemRenderer;

use BA\MeilisearchFrontendMinimal\Api\Layer\StateItemRendererInterface;
use Magento\Framework\View\Element\Template;

abstract class AbstractStateItemRenderer implements StateItemRendererInterface
{
    /**
     * @param array<string, mixed> $facet
     */
    protected function getFacetCode(array $facet): string
    {
        return (string)($facet['code'] ?? '');
    }

    /**
     * @param array<string, mixed> $facet
     */
    protected function getFacetLabel(array $facet): string
    {
        $facetCode = $this->getFacetCode($facet);
        return (string)($facet['label'] ?? $facetCode);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getSelectedValue(string $facetCode, array $params): string
    {
        $selected = $params[$facetCode] ?? '';
        return is_string($selected) ? trim($selected) : '';
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function buildRemoveUrl(Template $block, string $facetCode, array $params): string
    {
        $query = $params;
        unset($query[$facetCode]);

        return (string)$block->getUrl('*/*/*', ['_current' => false, '_use_rewrite' => true, '_query' => $query]);
    }

    protected function renderItemHtml(Template $block, string $filterName, string $filterValue, string $removeUrl): string
    {
        $filterNameText = (string)__($filterName);
        $removeTitle = (string)__('Remove')
            . ' '
            . $filterNameText
            . ($filterValue !== '' ? ' ' . $filterValue : '');

        $html = '<li class="item">';
        $html .= '<span class="filter-label">' . $block->escapeHtml($filterNameText) . '</span>';
        if ($filterValue !== '') {
            $html .= '<span class="filter-value">' . $block->escapeHtml($filterValue) . '</span>';
        }
        $html .= '<a class="action remove" href="' . $block->escapeUrl($removeUrl) . '"';
        $html .= ' title="' . $block->escapeHtmlAttr($removeTitle) . '">';
        $html .= '<span>' . $block->escapeHtml(__('Remove This Item')) . '</span>';
        $html .= '</a>';
        $html .= '</li>';

        return $html;
    }

    protected function normalizeDisplayValue(string $selectedValue): string
    {
        return str_replace(',', ', ', $selectedValue);
    }
}
