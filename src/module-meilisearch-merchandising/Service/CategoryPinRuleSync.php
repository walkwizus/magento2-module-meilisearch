<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\DynamicSearchRuleManager;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\PinRuleBuilder;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;

class CategoryPinRuleSync
{
    private const INDEX_TYPE = 'catalog_product';

    /**
     * @param DynamicSearchRuleManager $ruleManager
     * @param CategoryProductPosition $positionResource
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param StoreManagerInterface $storeManager
     * @param PinRuleBuilder $pinRuleBuilder
     */
    public function __construct(
        private readonly DynamicSearchRuleManager $ruleManager,
        private readonly CategoryProductPosition $positionResource,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly PinRuleBuilder $pinRuleBuilder
    ) { }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->ruleManager->isEnabled();
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return void
     * @throws \Exception
     */
    public function sync(int $storeId, int $categoryId): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $pins = [];
        foreach ($this->positionResource->getCategoryPositions($storeId, $categoryId) as $productId => $row) {
            if ($row['is_pinned']) {
                $pins[$productId] = $row['position'];
            }
        }

        $this->saveRule($this->getIndexName($storeId), $categoryId, $pins);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function syncAll(): int
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        $count = 0;

        foreach ($this->storeManager->getStores() as $store) {
            $indexName = $this->getIndexName((int) $store->getId());
            $pinsByCategory = $this->positionResource->getPinnedProducts((int) $store->getId());
            $expected = [];

            foreach ($pinsByCategory as $categoryId => $pins) {
                $this->saveRule($indexName, $categoryId, $pins);
                $expected[] = $this->pinRuleBuilder->getUid($indexName, $categoryId);
                $count++;
            }

            $uids = $this->ruleManager->getRuleUids($this->pinRuleBuilder->getUidPrefix($indexName));
            foreach (array_diff($uids, $expected) as $uid) {
                $this->ruleManager->deleteRule($uid);
            }
        }

        return $count;
    }

    /**
     * @param int $categoryId
     * @return void
     * @throws \Exception
     */
    public function deleteCategory(int $categoryId): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $this->ruleManager->deleteRule($this->pinRuleBuilder->getUid($this->getIndexName((int) $store->getId()), $categoryId));
        }
    }

    /**
     * @param string $indexName
     * @param int $categoryId
     * @param array $pins
     * @return void
     * @throws \Exception
     */
    private function saveRule(string $indexName, int $categoryId, array $pins): void
    {
        $uid = $this->pinRuleBuilder->getUid($indexName, $categoryId);

        if ($pins === []) {
            $this->ruleManager->deleteRule($uid);
            return;
        }

        $this->ruleManager->saveRule(
            $uid,
            $this->pinRuleBuilder->getConditions($categoryId),
            $this->pinRuleBuilder->getActions($indexName, $pins),
            $this->pinRuleBuilder->getDescription($categoryId),
            PinRuleBuilder::PRECEDENCE
        );
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
