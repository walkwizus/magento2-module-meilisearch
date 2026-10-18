<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\FacetRenderer;

use Magento\Catalog\Model\Layer\Filter\Price\Range as PriceRange;
use Magento\Catalog\Model\Layer\Filter\Price\Render as PriceRender;
use Magento\Framework\View\Element\Template;

class PriceRenderer extends AbstractFacetRenderer
{
    public function __construct(
        private readonly PriceRender $priceRender,
        private readonly PriceRange $priceRange
    ) {
    }

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

        $step = $this->getRangeStep();
        $grouped = $this->groupByPriceRange($options, $step);
        if ($grouped === []) {
            return '';
        }

        $selectedValues = $this->normalizeValueList(
            explode(',', $this->getSelectedValue($facetCode, $params))
        );

        $html = '<ol class="items">';

        foreach ($grouped as $group) {
            $queryValue = implode('-', [$group['from'], $group['to']]);
            $url = $this->buildFilterUrl($block, $facetCode, $queryValue, $params);
            $isActive = $selectedValues === $this->normalizeValueList($group['values']);
            $activeClass = $isActive ? ' active' : '';
            $rangeLabel = (string)$this->priceRender->renderRangeLabel($group['from'], $group['to']);

            $html .= '<li class="item filter-option filter-option-price">';
            $html .= '<a href="' . $block->escapeUrl($url) . '" class="' . trim($activeClass) . '">';
            $html .= $rangeLabel . ' <span class="count">(' . (int)$group['count'] . ')</span>';
            $html .= '</a>';
            $html .= '</li>';
        }

        return $html. '</ol>';
    }

    private function getRangeStep(): float
    {
        $range = (float)$this->priceRange->getConfigRangeStep();

        return $range > 0.0 ? $range : 10.0;
    }

    /**
     * @param array<string, int|string> $options
     * @return array<int, array{from:float,to:float,count:int,values:array<int, string>}>
     */
    private function groupByPriceRange(array $options, float $step): array
    {
        $rows = [];
        foreach ($options as $rawValue => $count) {
            $price = $this->parseNumericValue((string)$rawValue);
            if ($price === null) {
                continue;
            }

            $rows[] = [
                'raw' => (string)$rawValue,
                'price' => $price,
                'count' => (int)$count
            ];
        }

        if ($rows === []) {
            return [];
        }

        usort(
            $rows,
            static fn(array $a, array $b): int => $a['price'] <=> $b['price']
        );

        $step = max($step, 0.01);
        $groups = $this->buildGroups($rows, $step);

        // If everything falls into one bucket but prices vary, progressively reduce step.
        if (count($groups) <= 1 && $this->hasPriceSpread($rows)) {
            $adaptiveStep = $step;
            for ($i = 0; $i < 6 && count($groups) <= 1; $i++) {
                $adaptiveStep /= 10.0;
                if ($adaptiveStep < 0.01) {
                    break;
                }
                $groups = $this->buildGroups($rows, $adaptiveStep);
            }
        }

        return $groups;
    }

    /**
     * @param array<int, array{raw:string,price:float,count:int}> $rows
     * @return array<int, array{from:float,to:float,count:int,values:array<int, string>}>
     */
    private function buildGroups(array $rows, float $step): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $index = (int)floor($row['price'] / $step);
            $from = $index * $step;
            $to = $from + $step;
            $key = (string)$index;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'from' => $from,
                    'to' => $to,
                    'count' => 0,
                    'values' => []
                ];
            }

            $groups[$key]['count'] += $row['count'];
            $groups[$key]['values'][] = $row['raw'];
        }

        ksort($groups, SORT_NUMERIC);

        foreach ($groups as &$group) {
            $group['values'] = array_values(array_unique($group['values']));
        }

        return array_values($groups);
    }

    /**
     * @param array<int, array{raw:string,price:float,count:int}> $rows
     */
    private function hasPriceSpread(array $rows): bool
    {
        if ($rows === []) {
            return false;
        }

        $first = $rows[0]['price'];
        $last = $rows[count($rows) - 1]['price'];

        return $last > $first;
    }

    private function parseNumericValue(string $value): ?float
    {
        $normalized = trim(str_replace(',', '.', $value));
        if (is_numeric($normalized)) {
            return (float)$normalized;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);
        if (!is_string($normalized) || $normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float)$normalized;
    }

    /**
     * @param array<int, string> $values
     * @return array<int, string>
     */
    private function normalizeValueList(array $values): array
    {
        $clean = [];
        foreach ($values as $value) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                continue;
            }
            $clean[] = $trimmed;
        }

        $clean = array_values(array_unique($clean));
        sort($clean, SORT_STRING);

        return $clean;
    }
}
