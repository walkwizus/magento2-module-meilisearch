<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Meilisearch\Contracts\DocumentsQuery;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFacet\FacetFilterBuilder;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFilterResolver;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\SortFields;

class CategoryProductPreview
{
    private const INDEX_TYPE = 'catalog_product';
    private const SEARCH_LIMIT = 1000;
    private const DOCUMENTS_BATCH_SIZE = 1000;

    /**
     * @var array
     */
    private array $sortableAttributes = [];

    /**
     * @param SearchManager $searchManager
     * @param CategoryFilterResolver $categoryFilterResolver
     * @param FacetFilterBuilder $facetFilterBuilder
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param ConnectionManager $connectionManager
     * @param SortFields $sortFields
     */
    public function __construct(
        private readonly SearchManager $searchManager,
        private readonly CategoryFilterResolver $categoryFilterResolver,
        private readonly FacetFilterBuilder $facetFilterBuilder,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly ConnectionManager $connectionManager,
        private readonly SortFields $sortFields
    ) {
    }

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
        $listFilter = $facetFilter === '' ? $filter : '(' . $filter . ') AND ' . $facetFilter;
        $arrangement = $this->normalizeArrangement($arrangement);
        $offset = ($page - 1) * $pageSize;
        $buried = $this->getPresentIds($indexName, $filter, $arrangement['buried']);
        $sortable = $this->countDocuments($indexName, $filter) - count($buried);

        if ($query !== '') {
            $result = $this->searchManager->search($indexName, $query, [
                'filter' => $listFilter,
                'attributesToRetrieve' => ['id'],
                'limit' => self::SEARCH_LIMIT
            ]);
            $ids = array_map(static fn (array $hit): int => (int) $hit['id'], $result->getHits());
            $total = $result->getEstimatedTotalHits() ?? count($ids);
            $pageIds = array_slice($ids, $offset, $pageSize);

            return [
                'items' => $this->getDocuments($indexName, $pageIds, $this->getKnownPositions($pageIds, $arrangement), $arrangement),
                'total' => $total,
                'pageable' => count($ids),
                'sortable' => $sortable,
                'truncated' => $total > count($ids)
            ];
        }

        if ($facetFilter !== '') {
            $buried = $this->getPresentIds($indexName, $listFilter, $buried);
        }

        $total = $facetFilter === '' ? $sortable + count($buried) : $this->countDocuments($indexName, $listFilter);
        $arrangedCount = $total - count($buried);
        $orderedIds = $this->getOrderedIds($indexName, $listFilter, $categoryId, $arrangement, $offset + $pageSize);
        $pageIds = [];
        $positions = [];

        for ($index = $offset; $index < min($offset + $pageSize, $total); $index++) {
            $id = $index < $arrangedCount ? ($orderedIds[$index] ?? null) : ($buried[$index - $arrangedCount] ?? null);

            if ($id !== null) {
                $pageIds[] = $id;
                $positions[$id] = $index + 1;
            }
        }

        if ($facetFilter !== '') {
            $positions = $this->getKnownPositions($pageIds, $arrangement);
        }

        return [
            'items' => $this->getDocuments($indexName, $pageIds, $positions, $arrangement),
            'total' => $total,
            'pageable' => $total,
            'sortable' => $sortable,
            'truncated' => false
        ];
    }

    /**
     * @param array|null $rules
     * @param int $storeId
     * @param int $categoryId
     * @param array $arrangement
     * @param int $arrangedDepth
     * @return array
     * @throws \Exception
     */
    public function getPositions(?array $rules, int $storeId, int $categoryId, array $arrangement, int $arrangedDepth): array
    {
        $indexName = $this->getIndexName($storeId);
        $filter = $this->categoryFilterResolver->resolve($storeId, $categoryId, $rules);
        $arrangement = $this->normalizeArrangement($arrangement);
        $depth = max(
            $arrangedDepth,
            $arrangement['pins'] === [] ? 0 : max($arrangement['pins']),
            $arrangement['moves'] === [] ? 0 : max(array_map(static fn (array $move): int => (int) ($move['position'] ?? 0), $arrangement['moves']))
        );
        $orderedIds = array_slice($this->getOrderedIds($indexName, $filter, $categoryId, $arrangement, $depth), 0, $depth);
        $rows = [];

        foreach ($orderedIds as $index => $id) {
            $rows[] = [
                'product_id' => $id,
                'position' => $index + 1,
                'is_pinned' => isset($arrangement['pins'][$id]),
                'is_buried' => false
            ];
        }

        foreach ($this->getPresentIds($indexName, $filter, $arrangement['buried']) as $index => $id) {
            $rows[] = [
                'product_id' => $id,
                'position' => $index + 1,
                'is_pinned' => false,
                'is_buried' => true
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
            'buried' => $buried
        ];
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @param int $categoryId
     * @param array $arrangement
     * @param int $needed
     * @return array
     * @throws \Exception
     */
    private function getOrderedIds(string $indexName, string $filter, int $categoryId, array $arrangement, int $needed): array
    {
        $movedIds = array_map(static fn (array $move): int => (int) ($move['id'] ?? 0), $arrangement['moves']);
        $depth = $needed + count($arrangement['pins']) + count($arrangement['buried']) + count($movedIds);
        $orderedIds = $this->getSortedIds($indexName, $filter, $categoryId, $depth);
        $missingIds = array_diff(array_unique(array_merge(array_keys($arrangement['pins']), $movedIds)), $orderedIds);
        $orderedIds = array_merge($orderedIds, $this->getPresentIds($indexName, $filter, array_values($missingIds)));

        return $this->arrange($this->applyMoves($orderedIds, $arrangement['moves']), $arrangement);
    }

    /**
     * @param array $orderedIds
     * @param array $arrangement
     * @return array
     */
    private function arrange(array $orderedIds, array $arrangement): array
    {
        $pins = array_intersect_key($arrangement['pins'], array_flip($orderedIds));
        $excluded = array_flip(array_merge(array_keys($pins), $arrangement['buried']));
        $arranged = array_values(array_filter($orderedIds, static fn (int $id): bool => !isset($excluded[$id])));

        asort($pins);
        foreach ($pins as $id => $slot) {
            array_splice($arranged, min($slot - 1, count($arranged)), 0, [$id]);
        }

        return $arranged;
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
     * @param array $ids
     * @param array $arrangement
     * @return array
     */
    private function getKnownPositions(array $ids, array $arrangement): array
    {
        $positions = [];

        foreach ($ids as $id) {
            if (isset($arrangement['pins'][$id])) {
                $positions[$id] = $arrangement['pins'][$id];
            }
        }

        return $positions;
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @param int $categoryId
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    private function getSortedIds(string $indexName, string $filter, int $categoryId, int $limit): array
    {
        $index = $this->connectionManager->getConnection()->index($indexName);
        $sort = $this->getSort($indexName, $categoryId);
        $ids = [];

        while (count($ids) < $limit) {
            $documentsQuery = (new DocumentsQuery())
                ->setFilter([$filter])
                ->setFields(['id'])
                ->setOffset(count($ids))
                ->setLimit(min(self::DOCUMENTS_BATCH_SIZE, $limit - count($ids)));

            if ($sort !== []) {
                $documentsQuery->setSort($sort);
            }

            $documents = $index->getDocuments($documentsQuery)->getResults();

            foreach ($documents as $document) {
                $ids[] = (int) $document['id'];
            }

            if (count($documents) < self::DOCUMENTS_BATCH_SIZE) {
                break;
            }
        }

        return $ids;
    }

    /**
     * @param string $indexName
     * @param int $categoryId
     * @return array
     * @throws \Exception
     */
    private function getSort(string $indexName, int $categoryId): array
    {
        if (!isset($this->sortableAttributes[$indexName])) {
            $this->sortableAttributes[$indexName] = array_flip(
                $this->connectionManager->getConnection()->index($indexName)->getSortableAttributes()
            );
        }

        return array_values(array_filter(
            $this->sortFields->getSort($categoryId),
            fn (string $sort): bool => isset($this->sortableAttributes[$indexName][strstr($sort, ':', true)])
        ));
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @param array $ids
     * @return array
     * @throws \Exception
     */
    private function getPresentIds(string $indexName, string $filter, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $documents = $this->connectionManager->getConnection()->index($indexName)->getDocuments(
            (new DocumentsQuery())
                ->setFilter(['(' . $filter . ') AND id IN [' . implode(',', $ids) . ']'])
                ->setFields(['id'])
                ->setLimit(count($ids))
        )->getResults();
        $present = array_flip(array_map(static fn (array $document): int => (int) $document['id'], $documents));

        return array_values(array_filter($ids, static fn (int $id): bool => isset($present[$id])));
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
                    'merchandising_position' => $positions[$id] ?? null,
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
