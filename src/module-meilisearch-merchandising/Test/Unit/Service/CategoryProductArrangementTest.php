<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Test\Unit\Service;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Indexer\CacheContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;
use Walkwizus\MeilisearchMerchandising\Service\CategoryPinRuleSync;
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductArrangement;
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductPreview;
use Walkwizus\MeilisearchMerchandising\Service\SaveProductPosition;

class CategoryProductArrangementTest extends TestCase
{
    private CategoryProductPosition&MockObject $positionResource;

    private SaveProductPosition&MockObject $saveProductPosition;

    private CategoryPinRuleSync&MockObject $categoryPinRuleSync;

    private CategoryProductArrangement $arrangement;

    protected function setUp(): void
    {
        $this->positionResource = $this->createMock(CategoryProductPosition::class);
        $this->saveProductPosition = $this->createMock(SaveProductPosition::class);
        $this->categoryPinRuleSync = $this->createMock(CategoryPinRuleSync::class);

        $this->arrangement = new CategoryProductArrangement(
            $this->positionResource,
            $this->createMock(CategoryProductPreview::class),
            $this->saveProductPosition,
            $this->categoryPinRuleSync,
            $this->createMock(CacheContext::class),
            $this->createMock(EventManager::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testResetRemovesEveryPositionOfTheCategory(): void
    {
        $this->positionResource->method('getCategoryPositions')->with(2, 26)->willReturn([
            105177 => ['position' => 1, 'is_pinned' => true, 'is_buried' => false],
            73542 => ['position' => 2, 'is_pinned' => false, 'is_buried' => false],
            106181 => ['position' => 1, 'is_pinned' => false, 'is_buried' => true],
        ]);

        $this->positionResource->expects($this->once())->method('replaceCategoryPositions')->with(2, 26, []);
        $this->saveProductPosition->expects($this->once())->method('execute')->with(2, 26, [105177, 73542, 106181], []);
        $this->categoryPinRuleSync->expects($this->once())->method('sync')->with(2, 26);

        $this->arrangement->reset(2, 26);
    }

    public function testResetDoesNothingWithoutSavedOrder(): void
    {
        $this->positionResource->method('getCategoryPositions')->willReturn([]);

        $this->positionResource->expects($this->never())->method('replaceCategoryPositions');
        $this->saveProductPosition->expects($this->never())->method('execute');
        $this->categoryPinRuleSync->expects($this->never())->method('sync');

        $this->arrangement->reset(2, 26);
    }
}
