<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Meilisearch\Contracts\DocumentsQuery;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFacet\FacetFilterBuilder;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFilterResolver;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\RemainingSorter;

class CategoryProductPreview
{
    private const INDEX_TYPE = 'catalog_product';
    private const MAX_HITS = 10000;
    private const BURIED_POSITION_OFFSET = 1000000;

    /**
     * @param SearchManager $searchManager
     * @param CategoryFilterResolver $categoryFilterResolver
     * @param FacetFilterBuilder $facetFilterBuilder
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param ConnectionManager $connectionManager
     * @param RemainingSorter $remainingSorter
     */
    public function __construct(
        private readonly SearchManager $searchManager,
        private readonly CategoryFilterResolver $categoryFilterResolver,
        private readonly FacetFilterBuilder $facetFilterBuilder,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly ConnectionManager $connectionManager,
        private readonly RemainingSorter $remainingSorter
    ) { }

    /**
     * @param array|null $rules
     * @param int $storeId
     * @param int $categoryId
     * @param array $arrangement
     * @param int $page
     * @param int $pageSize
     * @param string $query
     * @param array $facetFilters
     * @return array
     * @throws \Exception
     */
    public function getPage(
        ?array $rules,
        int $storeId,
        int $categoryId,
        array $arrangement,
        int $page,
        int $pageSize,
        string $query = '',
        array $facetFilters = []
    ): array {
        $indexName = $this->getIndexName($storeId);
        $filter = $this->categoryFilterResolver->resolve($storeId, $categoryId, $rules);
        $facetFilter = $this->facetFilterBuilder->build($facetFilters);
        $isFiltered = $query !== '' || $facetFilter !== '';
        $arrangement = $this->normalizeArrangement($arrangement);
        $orderedIds = $this->getOrderedIds($indexName, $filter, $categoryId, $arrangement);
        $positions = array_flip($orderedIds);
        $total = $this->countDocuments($indexName, $filter);

        if ($isFiltered) {
            $searchFilter = $facetFilter === '' ? $filter : '(' . $filter . ') AND ' . $facetFilter;
            $searchIds = $this->searchIds($indexName, $searchFilter, $query);
            $matchingIds = array_flip($searchIds);
            $orderedIds = array_merge(
                array_values(array_filter($orderedIds, static fn (int $id): bool => isset($matchingIds[$id]))),
                array_values(array_filter($searchIds, static fn (int $id): bool => !isset($positions[$id])))
            );
            $total = count($orderedIds);
        }

        $pageIds = array_slice($orderedIds, ($page - 1) * $pageSize, $pageSize);

        return [
            'items' => $this->getDocuments($indexName, $pageIds, $positions, $arrangement),
            'total' => $total,
            'pageable' => count($orderedIds),
            'sortable' => count($positions),
            'truncated' => !$isFiltered && $total > count($positions)
        ];
    }

    /**
     * @param array|null $rules
     * @param int $storeId
     * @param int $categoryId
     * @param array $arrangement
     * @return array
     * @throws \Exception
     */
    public function getPositions(?array $rules, int $storeId, int $categoryId, array $arrangement): array
    {
        $indexName = $this->getIndexName($storeId);
        $filter = $this->categoryFilterResolver->resolve($storeId, $categoryId, $rules);
        $arrangement = $this->normalizeArrangement($arrangement);
        $orderedIds = $this->getOrderedIds($indexName, $filter, $categoryId, $arrangement);
        $buried = array_flip($arrangement['buried']);
        $rows = [];
        $buriedIndex = 0;

        foreach ($orderedIds as $index => $id) {
            $isBuried = isset($buried[$id]);
            $rows[] = [
                'id' => $id,
                'position' => $isBuried ? self::BURIED_POSITION_OFFSET + ++$buriedIndex : $index + 1,
                'is_pinned' => isset($arrangement['pins'][$id]),
                'is_buried' => $isBuried
            ];
        }

        return $rows;
    }

    /**
     * @param array $arrangement
     * @return array
     */
    public function normalizeArrangement(array $arrangement): array
    {
        $pins = [];

        foreach ((array) ($arrangement['pins'] ?? []) as $id => $slot) {
            if ((int) $id > 0 && (int) $slot > 0) {
                $pins[(int) $id] = (int) $slot;
            }
        }

        $buried = array_values(array_unique(array_filter(array_map('intval', (array) ($arrangement['buried'] ?? [])))));

        return [
            'moves' => array_values(array_filter((array) ($arrangement['moves'] ?? []), 'is_array')),
            'pins' => array_diff_key($pins, array_flip($buried)),
            'buried' => $buried,
            'sortBy' => $this->remainingSorter->normalize((string) ($arrangement['sortBy'] ?? RemainingSorter::SORT_POSITION))
        ];
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @param int $categoryId
     * @param array $arrangement
     * @return array
     * @throws \Exception
     */
    private function getOrderedIds(string $indexName, string $filter, int $categoryId, array $arrangement): array
    {
        $result = $this->searchManager->search($indexName, '', [
            'filter' => $filter,
            'sort' => ['position_category_' . $categoryId . ':asc'],
            'attributesToRetrieve' => ['id'],
            'limit' => self::MAX_HITS
        ]);

        $orderedIds = array_map(static fn (array $hit): int => (int) $hit['id'], $result->getHits());

        return $this->arrange($this->applyMoves($orderedIds, $arrangement['moves']), $arrangement, $indexName);
    }

    /**
     * @param array $orderedIds
     * @param array $arrangement
     * @param string $indexName
     * @return array
     * @throws \Exception
     */
    private function arrange(array $orderedIds, array $arrangement, string $indexName): array
    {
        $present = array_flip($orderedIds);
        $pins = array_intersect_key($arrangement['pins'], $present);
        $buried = array_values(array_filter($arrangement['buried'], static fn (int $id): bool => isset($present[$id])));
        $excluded = array_flip(array_merge(array_keys($pins), $buried));
        $remaining = array_values(array_filter($orderedIds, static fn (int $id): bool => !isset($excluded[$id])));
        $remaining = $this->remainingSorter->sort($remaining, $arrangement['sortBy'], $indexName);

        asort($pins);
        foreach ($pins as $id => $slot) {
            array_splice($remaining, min($slot - 1, count($remaining)), 0, [$id]);
        }

        return array_merge($remaining, $buried);
    }

    /**
     * @param array $orderedIds
     * @param array $moves
     * @return array
     */
    private function applyMoves(array $orderedIds, array $moves): array
    {
        foreach ($moves as $move) {
            $index = array_search((int) ($move['id'] ?? 0), $orderedIds, true);

            if ($index === false) {
                continue;
            }

            $id = $orderedIds[$index];
            array_splice($orderedIds, $index, 1);
            $position = max(0, min((int) ($move['position'] ?? 1) - 1, count($orderedIds)));
            array_splice($orderedIds, $position, 0, [$id]);
        }

        return $orderedIds;
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @param string $query
     * @return array
     * @throws \Exception
     */
    private function searchIds(string $indexName, string $filter, string $query): array
    {
        $result = $this->searchManager->search($indexName, $query, [
            'filter' => $filter,
            'attributesToRetrieve' => ['id'],
            'limit' => self::MAX_HITS
        ]);

        return array_map(static fn (array $hit): int => (int) $hit['id'], $result->getHits());
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @return int
     * @throws \Exception
     */
    private function countDocuments(string $indexName, string $filter): int
    {
        $documentsQuery = (new DocumentsQuery())->setLimit(1)->setFields(['id']);

        if ($filter !== '') {
            $documentsQuery->setFilter([$filter]);
        }

        return $this->connectionManager->getConnection()->index($indexName)->getDocuments($documentsQuery)->getTotal();
    }

    /**
     * @param string $indexName
     * @param array $ids
     * @param array $positions
     * @param array $arrangement
     * @return array
     * @throws \Exception
     */
    private function getDocuments(string $indexName, array $ids, array $positions, array $arrangement): array
    {
        if ($ids === []) {
            return [];
        }

        $result = $this->searchManager->search($indexName, '', [
            'filter' => 'id IN [' . implode(',', $ids) . ']',
            'limit' => count($ids)
        ]);

        $documents = [];
        foreach ($result->getHits() as $hit) {
            $documents[(int) $hit['id']] = $hit;
        }

        $buried = array_flip($arrangement['buried']);
        $items = [];
        foreach ($ids as $id) {
            if (isset($documents[$id])) {
                $items[] = $documents[$id] + [
                    'merchandising_position' => isset($positions[$id]) ? $positions[$id] + 1 : null,
                    'merchandising_pinned' => isset($arrangement['pins'][$id]),
                    'merchandising_buried' => isset($buried[$id])
                ];
            }
        }

        return $items;
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getIndexName(int $storeId): string
    {
        return $this->searchIndexNameResolver->getIndexName($storeId, self::INDEX_TYPE);
    }
}
