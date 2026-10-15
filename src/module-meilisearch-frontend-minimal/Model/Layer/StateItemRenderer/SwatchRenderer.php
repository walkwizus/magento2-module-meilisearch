<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Model\Layer\StateItemRenderer;

class SwatchRenderer extends CheckboxRenderer
{
    protected function normalizeDisplayValue(string $selectedValue): string
    {
        $values = array_filter(array_map('trim', explode(',', $selectedValue)));
        if ($values === []) {
            return '';
        }

        $labels = [];
        foreach ($values as $value) {
            $labels[] = $this->extractSwatchLabel($value);
        }

        return implode(', ', $labels);
    }

    private function extractSwatchLabel(string $value): string
    {
        $matches = [];
        if (preg_match('/^(.*)\|[0-3]\|.*$/', $value, $matches) !== 1) {
            return $value;
        }

        $label = trim((string)($matches[1] ?? ''));
        return $label !== '' ? $label : $value;
    }
}

