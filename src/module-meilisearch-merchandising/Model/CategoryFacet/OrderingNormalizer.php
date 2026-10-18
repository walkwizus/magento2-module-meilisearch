<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryFacet;

class OrderingNormalizer
{
    public const SORT_DEFAULT = 'default';
    public const SORT_COUNT = 'count';
    public const SORT_ALPHA = 'alpha';
    public const SORT_HIDDEN = 'hidden';

    private const SORT_OPTIONS = [self::SORT_DEFAULT, self::SORT_COUNT, self::SORT_ALPHA, self::SORT_HIDDEN];
    private const BOOLEAN_SETTINGS = ['showMore', 'searchable', 'searchboxFuzzyEnabled', 'hideIfNonDiscriminant'];

    /**
     * @param array $ordering
     * @return array
     */
    public function normalize(array $ordering): array
    {
        $values = [];

        foreach ((array) ($ordering['values'] ?? []) as $code => $valueOrdering) {
            if (!is_array($valueOrdering)) {
                continue;
            }

            $normalized = [
                'order' => $this->normalizeList($valueOrdering['order'] ?? []),
                'hidden' => $this->normalizeList($valueOrdering['hidden'] ?? []),
                'sortRemainingBy' => in_array($valueOrdering['sortRemainingBy'] ?? null, self::SORT_OPTIONS, true)
                    ? $valueOrdering['sortRemainingBy']
                    : self::SORT_DEFAULT
            ];

            if ($normalized['order'] !== [] || $normalized['hidden'] !== [] || $normalized['sortRemainingBy'] !== self::SORT_DEFAULT) {
                $values[(string) $code] = $normalized;
            }
        }

        return [
            'facets' => [
                'order' => $this->normalizeList($ordering['facets']['order'] ?? []),
                'hidden' => $this->normalizeList($ordering['facets']['hidden'] ?? [])
            ],
            'values' => $values,
            'settings' => $this->normalizeSettings((array) ($ordering['settings'] ?? []))
        ];
    }

    /**
     * @param array $settings
     * @return array
     */
    private function normalizeSettings(array $settings): array
    {
        $normalized = [];

        foreach ($settings as $code => $facetSettings) {
            if (!is_array($facetSettings)) {
                continue;
            }

            $values = [];

            foreach (self::BOOLEAN_SETTINGS as $setting) {
                if (array_key_exists($setting, $facetSettings) && $facetSettings[$setting] !== null) {
                    $values[$setting] = (bool) $facetSettings[$setting];
                }
            }

            if (isset($facetSettings['showMoreLimit']) && (int) $facetSettings['showMoreLimit'] > 0) {
                $values['showMoreLimit'] = (int) $facetSettings['showMoreLimit'];
            }

            if ($values !== []) {
                $normalized[(string) $code] = $values;
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $list
     * @return array
     */
    private function normalizeList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $items = array_map('strval', array_filter($list, 'is_scalar'));

        return array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));
    }
}
