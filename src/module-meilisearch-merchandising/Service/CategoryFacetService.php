<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\CacheContext;
use Meilisearch\Contracts\DocumentsQuery;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider\FacetConfigProvider;
use Walkwizus\MeilisearchMerchandising\Api\CategoryFacetOrderingResolverInterface;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFacet\FacetFilterBuilder;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFacet\OrderingNormalizer;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryFacet;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFilterResolver;

class CategoryFacetService
{
    private const INDEX_TYPE = 'catalog_product';
    private const FACET_FIELDS = [
        'code', 'label', 'type', 'position', 'renderRegion', 'isSwatch', 'showMore', 'showMoreLimit',
        'searchable', 'searchboxFuzzyEnabled', 'sortValuesBy', 'hideIfNonDiscriminant'
    ];

    /**
     * @param AttributeProvider $attributeProvider
     * @param FacetConfigProvider $facetConfigProvider
     * @param CategoryFacet $categoryFacetResource
     * @param CategoryFacetOrderingResolverInterface $orderingResolver
     * @param OrderingNormalizer $orderingNormalizer
     * @param FacetFilterBuilder $facetFilterBuilder
     * @param CategoryRepositoryInterface $categoryRepository
     * @param SearchManager $searchManager
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param CategoryFilterResolver $categoryFilterResolver
     * @param ConnectionManager $connectionManager
     * @param CacheContext $cacheContext
     * @param EventManager $eventManager
     */
    public function __construct(
        private readonly AttributeProvider $attributeProvider,
        private readonly FacetConfigProvider $facetConfigProvider,
        private readonly CategoryFacet $categoryFacetResource,
        private readonly CategoryFacetOrderingResolverInterface $orderingResolver,
        private readonly OrderingNormalizer $orderingNormalizer,
        private readonly FacetFilterBuilder $facetFilterBuilder,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SearchManager $searchManager,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly CategoryFilterResolver $categoryFilterResolver,
        private readonly ConnectionManager $connectionManager,
        private readonly CacheContext $cacheContext,
        private readonly EventManager $eventManager
    ) { }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return array
     * @throws LocalizedException
     */
    public function getState(int $storeId, int $categoryId): array
    {
        return [
            'facets' => $this->getFacets(),
            'ordering' => $this->orderingResolver->resolve($storeId, $categoryId),
            'isAnchor' => $this->isAnchor($storeId, $categoryId)
        ];
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $ordering
     * @return array
     * @throws LocalizedException
     */
    public function save(int $storeId, int $categoryId, array $ordering): array
    {
        $ordering = $this->orderingNormalizer->normalize($ordering);
        $this->categoryFacetResource->saveOrdering($storeId, $categoryId, $ordering);
        $this->cleanCategoryCache($categoryId);

        return $ordering;
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return void
     * @throws LocalizedException
     */
    public function reset(int $storeId, int $categoryId): void
    {
        $this->categoryFacetResource->deleteOrdering($storeId, $categoryId);
        $this->cleanCategoryCache($categoryId);
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $targetStoreIds
     * @param array $targetCategoryIds
     * @param bool $includeChildren
     * @param bool $overwrite
     * @return array
     * @throws LocalizedException
     */
    public function copy(
        int $storeId,
        int $categoryId,
        array $targetStoreIds,
        array $targetCategoryIds,
        bool $includeChildren,
        bool $overwrite
    ): array {
        $ordering = $this->orderingResolver->resolve($storeId, $categoryId);

        if ($ordering === null) {
            throw new LocalizedException(__('Save a facet configuration for this category before copying it.'));
        }

        $categoryIds = $this->expandCategoryIds(array_map('intval', $targetCategoryIds), $includeChildren);
        $copied = 0;
        $skipped = 0;

        foreach (array_unique(array_map('intval', $targetStoreIds)) as $targetStoreId) {
            $existing = $this->categoryFacetResource->getOrderings($targetStoreId, $categoryIds);

            foreach ($categoryIds as $targetCategoryId) {
                if ($targetStoreId === $storeId && $targetCategoryId === $categoryId) {
                    continue;
                }

                if (!$overwrite && isset($existing[$targetCategoryId])) {
                    $skipped++;
                    continue;
                }

                $this->categoryFacetResource->saveOrdering($targetStoreId, $targetCategoryId, $ordering);
                $copied++;
            }
        }

        if ($copied > 0) {
            $this->cacheContext->registerEntities(Category::CACHE_TAG, $categoryIds);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
        }

        return ['copied' => $copied, 'skipped' => $skipped];
    }

    /**
     * @param array $categoryIds
     * @param bool $includeChildren
     * @return array
     */
    private function expandCategoryIds(array $categoryIds, bool $includeChildren): array
    {
        if (!$includeChildren) {
            return array_values(array_unique(array_filter($categoryIds)));
        }

        $expanded = [];

        foreach (array_filter($categoryIds) as $categoryId) {
            try {
                /** @var Category $category */
                $category = $this->categoryRepository->get($categoryId);
                $expanded = array_merge($expanded, array_map('intval', explode(',', (string) $category->getAllChildren())));
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }

        return array_values(array_unique(array_filter($expanded)));
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array|null $rules
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function preview(int $storeId, int $categoryId, ?array $rules, array $filters): array
    {
        $indexName = $this->searchIndexNameResolver->getIndexName($storeId, self::INDEX_TYPE);
        $baseFilter = $this->categoryFilterResolver->resolve($storeId, $categoryId, $rules);
        $codes = array_column($this->getFacets(), 'code');
        $filters = array_intersect_key($filters, array_flip($codes));
        $result = $this->searchManager->search($indexName, '', [
            'filter' => $this->combine($baseFilter, $this->facetFilterBuilder->build($filters)),
            'facets' => $codes,
            'limit' => 0
        ]);
        $distribution = $result->getFacetDistribution();

        foreach (array_keys(array_filter($filters)) as $code) {
            $disjunctive = $this->searchManager->search($indexName, '', [
                'filter' => $this->combine($baseFilter, $this->facetFilterBuilder->build($filters, (string) $code)),
                'facets' => [(string) $code],
                'limit' => 0
            ]);
            $distribution[$code] = $disjunctive->getFacetDistribution()[$code] ?? [];
        }

        $documentsQuery = (new DocumentsQuery())
            ->setLimit(1)
            ->setFields(['id'])
            ->setFilter([$this->combine($baseFilter, $this->facetFilterBuilder->build($filters))]);

        return [
            'facetDistribution' => (object) $distribution,
            'facetStats' => (object) $result->getFacetStats(),
            'totalHits' => $this->connectionManager->getConnection()->index($indexName)->getDocuments($documentsQuery)->getTotal()
        ];
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getFacets(): array
    {
        $attributes = $this->attributeProvider->getFilterableAttributes(SearchIndexNameResolver::DEFAULT_INDEX, 'frontend');
        $facets = array_map(
            static fn (array $facet): array => array_intersect_key($facet, array_flip(self::FACET_FIELDS)),
            array_values($this->facetConfigProvider->getFacetList($attributes))
        );

        usort($facets, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return $facets;
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return bool
     */
    private function isAnchor(int $storeId, int $categoryId): bool
    {
        try {
            /** @var Category $category */
            $category = $this->categoryRepository->get($categoryId, $storeId);
        } catch (NoSuchEntityException $e) {
            return true;
        }

        return (bool) $category->getData('is_anchor');
    }

    /**
     * @param string $baseFilter
     * @param string $facetFilter
     * @return string
     */
    private function combine(string $baseFilter, string $facetFilter): string
    {
        return $facetFilter === '' ? $baseFilter : '(' . $baseFilter . ') AND ' . $facetFilter;
    }

    /**
     * @param int $categoryId
     * @return void
     */
    private function cleanCategoryCache(int $categoryId): void
    {
        $this->cacheContext->registerEntities(Category::CACHE_TAG, [$categoryId]);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
