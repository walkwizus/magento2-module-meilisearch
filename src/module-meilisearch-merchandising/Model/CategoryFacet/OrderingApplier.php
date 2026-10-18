<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryFacet;

class OrderingApplier
{
    /**
     * @param array $facetList
     * @param array $facetConfig
     * @param array $ordering
     * @return array
     */
    public function apply(array $facetList, array $facetConfig, array $ordering): array
    {
        $hidden = array_flip($ordering['facets']['hidden'] ?? []);
        $available = array_values(array_filter($facetList, static fn (string $code): bool => !isset($hidden[$code])));
        $pinned = array_values(array_intersect($ordering['facets']['order'] ?? [], $available));
        $orderedList = array_merge($pinned, array_values(array_diff($available, $pinned)));

        foreach ($orderedList as $position => $code) {
            if (!isset($facetConfig[$code])) {
                continue;
            }

            $facetConfig[$code] = array_merge($facetConfig[$code], $ordering['settings'][$code] ?? []);
            $facetConfig[$code]['position'] = $position;

            if (isset($ordering['values'][$code])) {
                $facetConfig[$code]['valueOrdering'] = $ordering['values'][$code];
            }
        }

        return [$orderedList, $facetConfig];
    }
}
