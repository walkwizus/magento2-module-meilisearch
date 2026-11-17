<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Test\Unit\Service;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchBase\Service\DynamicSearchRuleManager;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\PinRuleBuilder;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;
use Walkwizus\MeilisearchMerchandising\Service\CategoryPinRuleSync;

class CategoryPinRuleSyncTest extends TestCase
{
    private DynamicSearchRuleManager&MockObject $ruleManager;

    private CategoryProductPosition&MockObject $positionResource;

    private CategoryPinRuleSync $sync;

    protected function setUp(): void
    {
        $this->ruleManager = $this->createMock(DynamicSearchRuleManager::class);
        $this->positionResource = $this->createMock(CategoryProductPosition::class);

        $resolver = $this->createMock(SearchIndexNameResolver::class);
        $resolver->method('getIndexName')->willReturnCallback(
            static fn (int $storeId): string => 'mageos_catalog_product_' . $storeId
        );

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(2);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);

        $this->sync = new CategoryPinRuleSync(
            $this->ruleManager,
            $this->positionResource,
            $resolver,
            $storeManager,
            new PinRuleBuilder()
        );
    }

    public function testNothingIsSentWithoutSearchRules(): void
    {
        $this->ruleManager->method('isEnabled')->willReturn(false);
        $this->positionResource->expects($this->never())->method('getCategoryPositions');
        $this->ruleManager->expects($this->never())->method('saveRule');
        $this->ruleManager->expects($this->never())->method('deleteRule');

        $this->sync->sync(2, 26);
        $this->sync->deleteCategory(26);
        $this->assertSame(0, $this->sync->syncAll());
    }

    public function testRuleOnlyPinsPinnedProducts(): void
    {
        $this->ruleManager->method('isEnabled')->willReturn(true);
        $this->positionResource->method('getCategoryPositions')->willReturn([
            105177 => ['position' => 1, 'is_pinned' => true, 'is_buried' => false],
            73542 => ['position' => 3, 'is_pinned' => false, 'is_buried' => false],
            106307 => ['position' => 4, 'is_pinned' => true, 'is_buried' => false],
        ]);

        $this->ruleManager->expects($this->once())->method('saveRule')->with(
            'mageos_catalog_product_2-category-26',
            ['query' => ['isEmpty' => true], 'filter' => ['values' => ['category_ids' => 26]]],
            ['pin' => [
                ['id' => '105177', 'position' => 0, 'indexUid' => 'mageos_catalog_product_2'],
                ['id' => '106307', 'position' => 3, 'indexUid' => 'mageos_catalog_product_2'],
            ]],
            'Magento category 26 pinned products',
            PinRuleBuilder::PRECEDENCE
        );
        $this->ruleManager->expects($this->never())->method('deleteRule');

        $this->sync->sync(2, 26);
    }

    public function testRuleIsDeletedWhenNoProductIsPinned(): void
    {
        $this->ruleManager->method('isEnabled')->willReturn(true);
        $this->positionResource->method('getCategoryPositions')->willReturn([
            73542 => ['position' => 1, 'is_pinned' => false, 'is_buried' => false],
        ]);

        $this->ruleManager->expects($this->never())->method('saveRule');
        $this->ruleManager->expects($this->once())->method('deleteRule')->with('mageos_catalog_product_2-category-26');

        $this->sync->sync(2, 26);
    }

    public function testSyncAllRemovesRulesOfCategoriesWithoutPins(): void
    {
        $this->ruleManager->method('isEnabled')->willReturn(true);
        $this->positionResource->method('getPinnedProducts')->with(2)->willReturn([4 => [1362 => 2], 26 => [105177 => 1]]);
        $this->ruleManager->method('getRuleUids')->with('mageos_catalog_product_2-category-')->willReturn([
            'mageos_catalog_product_2-category-4',
            'mageos_catalog_product_2-category-26',
            'mageos_catalog_product_2-category-31',
        ]);

        $this->ruleManager->expects($this->exactly(2))->method('saveRule');
        $this->ruleManager->expects($this->once())->method('deleteRule')->with('mageos_catalog_product_2-category-31');

        $this->assertSame(2, $this->sync->syncAll());
    }
}
