<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Test\Unit\Model\CategoryProduct;

use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\PinRuleBuilder;

class PinRuleBuilderTest extends TestCase
{
    public function testUidIsScopedToIndexAndCategory(): void
    {
        $builder = new PinRuleBuilder();

        $this->assertSame('mageos_catalog_product_2-category-26', $builder->getUid('mageos_catalog_product_2', 26));
        $this->assertStringStartsWith($builder->getUidPrefix('mageos_catalog_product_2'), $builder->getUid('mageos_catalog_product_2', 4));
    }

    public function testConditionsMatchCategoryPagesWithoutQuery(): void
    {
        $this->assertSame(
            ['query' => ['isEmpty' => true], 'filter' => ['values' => ['category_ids' => 26]]],
            (new PinRuleBuilder())->getConditions(26)
        );
    }

    public function testActionsPinProductsAtZeroBasedPositionsInSlotOrder(): void
    {
        $this->assertSame(
            ['pin' => [
                ['id' => '1362', 'position' => 0, 'indexUid' => 'idx'],
                ['id' => '467', 'position' => 2, 'indexUid' => 'idx'],
            ]],
            (new PinRuleBuilder())->getActions('idx', [467 => 3, 1362 => 1])
        );
    }
}
