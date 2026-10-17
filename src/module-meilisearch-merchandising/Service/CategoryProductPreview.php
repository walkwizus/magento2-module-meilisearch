<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Meilisearch\Contracts\DocumentsQuery;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\SearchManager;

class CategoryProductPreview
{
    private const INDEX_TYPE = 'catalog_product';
    private const MAX_HITS = 10000;

    /**
     * @param SearchManager $searchManager
     * @param QueryBuilderService $queryBuilderService
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param ConnectionManager $connectionManager
     */
    public function __construct(
        private readonly SearchManager $searchManager,
        private readonly QueryBuilderService $queryBuilderService,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly ConnectionManager $connectionManager
    ) { }

    /**
     * @param array $rules
     * @param int $storeId
     * @param int $categoryId
     * @param array $moves
     * @param int $page
     * @param int $pageSize
     * @param string $query
     * @return array
     * @throws \Exception
     */
    public function getPage(
        array $rules,
        int $storeId,
        int $categoryId,
        array $moves,
        int $page,
        int $pageSize,
        string $query = ''
    ): array {
        $indexName = $this->getIndexName($storeId);
        $filter = $this->queryBuilderService->convertRulesToMeilisearchQuery($rules, $storeId);
        $orderedIds = $this->getOrderedIds($indexName, $filter, $categoryId, $moves);
        $positions = array_flip($orderedIds);
        $total = $this->countDocuments($indexName, $filter);

        if ($query !== '') {
            $searchIds = $this->searchIds($indexName, $filter, $query);
            $matchingIds = array_flip($searchIds);
            $orderedIds = array_merge(
                array_values(array_filter($orderedIds, static fn (int $id): bool => isset($matchingIds[$id]))),
                array_values(array_filter($searchIds, static fn (int $id): bool => !isset($positions[$id])))
            );
            $total = count($orderedIds);
        }

        $pageIds = array_slice($orderedIds, ($page - 1) * $pageSize, $pageSize);

        return [
            'items' => $this->getDocuments($indexName, $pageIds, $positions),
            'total' => $total,
            'pageable' => count($orderedIds),
            'sortable' => count($positions),
            'truncated' => $query === '' && $total > count($positions)
        ];
    }

    /**
     * @param array $rules
     * @param int $storeId
     * @param int $categoryId
     * @param array $moves
     * @return array
     * @throws \Exception
     */
    public function getPositions(array $rules, int $storeId, int $categoryId, array $moves): array
    {
        $indexName = $this->getIndexName($storeId);
        $filter = $this->queryBuilderService->convertRulesToMeilisearchQuery($rules, $storeId);
        $orderedIds = $this->getOrderedIds($indexName, $filter, $categoryId, $moves);

        return array_map(
            static fn (int $id, int $index): array => ['id' => $id, 'position' => $index + 1],
            $orderedIds,
            array_keys($orderedIds)
        );
    }

    /**
     * @param string $indexName
     * @param string $filter
     * @param int $categoryId
     * @param array $moves
     * @return array
     * @throws \Exception
     */
    private function getOrderedIds(string $indexName, string $filter, int $categoryId, array $moves): array
    {
        $result = $this->searchManager->search($indexName, '', [
            'filter' => $filter,
            'sort' => ['position_category_' . $categoryId . ':asc'],
            'attributesToRetrieve' => ['id'],
            'limit' => self::MAX_HITS
        ]);

        $orderedIds = array_map(static fn (array $hit): int => (int) $hit['id'], $result->getHits());

        return $this->applyMoves($orderedIds, $moves);
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
     * @return array
     * @throws \Exception
     */
    private function getDocuments(string $indexName, array $ids, array $positions): array
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

        $items = [];
        foreach ($ids as $id) {
            if (isset($documents[$id])) {
                $items[] = $documents[$id] + [
                    'merchandising_position' => isset($positions[$id]) ? $positions[$id] + 1 : null
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
