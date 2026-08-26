<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\FacetRenderer;

use BA\MeilisearchFrontendMinimal\Api\Layer\FacetRendererInterface;
use Magento\Framework\View\Element\Template;

abstract class AbstractFacetRenderer implements FacetRendererInterface
{
    /**
     * @param array<string, mixed> $facet
     */
    protected function getFacetCode(array $facet): string
    {
        return (string)($facet['code'] ?? '');
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getSelectedValue(string $facetCode, array $params): string
    {
        $selected = $params[$facetCode] ?? '';
        return is_string($selected) ? $selected : '';
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function buildFilterUrl(Template $block, string $facetCode, string $value, array $params): string
    {
        $query = $params;
        $query[$facetCode] = $value;

        return (string)$block->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }
}

