<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\CacheContext;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;

class CategoryProductArrangement
{
    /**
     * @param CategoryProductPosition $positionResource
     * @param CategoryProductPreview $categoryProductPreview
     * @param SaveProductPosition $saveProductPosition
     * @param CategoryPinRuleSync $categoryPinRuleSync
     * @param CacheContext $cacheContext
     * @param EventManager $eventManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CategoryProductPosition $positionResource,
        private readonly CategoryProductPreview $categoryProductPreview,
        private readonly SaveProductPosition $saveProductPosition,
        private readonly CategoryPinRuleSync $categoryPinRuleSync,
        private readonly CacheContext $cacheContext,
        private readonly EventManager $eventManager,
        private readonly LoggerInterface $logger
    ) {
    }

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
            'buried' => $buried
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
        $previous = $this->positionResource->getCategoryPositions($storeId, $categoryId);
        $arrangedDepth = count(array_filter($previous, static fn (array $row): bool => !$row['is_buried']));
        $rows = $this->categoryProductPreview->getPositions($rules, $storeId, $categoryId, $arrangement, $arrangedDepth);

        $this->apply($storeId, $categoryId, array_keys($previous), $rows);
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return bool
     * @throws LocalizedException
     */
    public function isArranged(int $storeId, int $categoryId): bool
    {
        return $this->positionResource->getCategoryPositions($storeId, $categoryId) !== [];
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return void
     * @throws \Exception
     */
    public function reset(int $storeId, int $categoryId): void
    {
        $previous = $this->positionResource->getCategoryPositions($storeId, $categoryId);

        if ($previous === []) {
            return;
        }

        $this->apply($storeId, $categoryId, array_keys($previous), []);
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $previousProductIds
     * @param array $rows
     * @return void
     * @throws \Exception
     */
    private function apply(int $storeId, int $categoryId, array $previousProductIds, array $rows): void
    {
        $this->positionResource->replaceCategoryPositions($storeId, $categoryId, $rows);
        $this->saveProductPosition->execute(
            $storeId,
            $categoryId,
            array_values(array_unique(array_merge($previousProductIds, array_map('intval', array_column($rows, 'product_id'))))),
            $rows
        );

        try {
            $this->categoryPinRuleSync->sync($storeId, $categoryId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        $this->cacheContext->registerEntities(Category::CACHE_TAG, [$categoryId]);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }
}
