<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\CacheContext;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\RemainingSorter;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategorySort;

class CategoryProductArrangement
{
    /**
     * @param CategoryProductPosition $positionResource
     * @param CategorySort $sortResource
     * @param CategoryProductPreview $categoryProductPreview
     * @param SaveProductPosition $saveProductPosition
     * @param CacheContext $cacheContext
     * @param EventManager $eventManager
     */
    public function __construct(
        private readonly CategoryProductPosition $positionResource,
        private readonly CategorySort $sortResource,
        private readonly CategoryProductPreview $categoryProductPreview,
        private readonly SaveProductPosition $saveProductPosition,
        private readonly CacheContext $cacheContext,
        private readonly EventManager $eventManager
    ) { }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return array
     * @throws LocalizedException
     */
    public function getSaved(int $storeId, int $categoryId): array
    {
        $pins = [];
        $buried = [];

        foreach ($this->positionResource->getCategoryPositions($storeId, $categoryId) as $productId => $row) {
            if ($row['is_pinned']) {
                $pins[$productId] = $row['position'];
            } elseif ($row['is_buried']) {
                $buried[] = $productId;
            }
        }

        return [
            'pins' => (object) $pins,
            'buried' => $buried,
            'sortBy' => $this->sortResource->getSortBy($storeId, $categoryId) ?? RemainingSorter::SORT_POSITION
        ];
    }

    /**
     * @param array|null $rules
     * @param int $storeId
     * @param int $categoryId
     * @param array $arrangement
     * @return void
     * @throws \Exception
     */
    public function save(?array $rules, int $storeId, int $categoryId, array $arrangement): void
    {
        $arrangement = $this->categoryProductPreview->normalizeArrangement($arrangement);
        $positions = $this->categoryProductPreview->getPositions($rules, $storeId, $categoryId, $arrangement);

        $this->positionResource->replaceCategoryPositions($storeId, $categoryId, array_map(
            static fn (array $row): array => [
                'product_id' => $row['id'],
                'position' => $row['position'],
                'is_pinned' => $row['is_pinned'],
                'is_buried' => $row['is_buried']
            ],
            $positions
        ));
        $this->sortResource->saveSortBy($storeId, $categoryId, $arrangement['sortBy']);
        $this->saveProductPosition->execute(
            array_map(static fn (array $row): array => ['id' => $row['id'], 'position' => $row['position']], $positions),
            $categoryId,
            $storeId
        );

        $this->cacheContext->registerEntities(Category::CACHE_TAG, [$categoryId]);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
