<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Test\Unit\Model\AttributeMapper\CatalogProduct;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchCatalog\Model\AttributeMapper\CatalogProduct\Category;

class CategoryTest extends TestCase
{
    public function testMapBuildsCategoryAndPositionAttributesPerProduct(): void
    {
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $tableMaintainer = $this->createMock(TableMaintainer::class);

        $rows = [
            ['product_id' => 10, 'category_id' => 4, 'position' => 12],
            ['product_id' => 10, 'category_id' => 8, 'position' => 1],
            ['product_id' => 20, 'category_id' => 4, 'position' => 3],
        ];

        $mapper = new class($resourceConnection, $tableMaintainer, $rows) extends Category {
            public array $capturedProductIds = [];
            public $capturedStoreId;

            public function __construct(
                ResourceConnection $resourceConnection,
                TableMaintainer $tableMaintainer,
                private readonly array $rows
            ) {
                parent::__construct($resourceConnection, $tableMaintainer);
            }

            protected function getProductsCategories(array $productIds, $storeId): array
            {
                $this->capturedProductIds = $productIds;
                $this->capturedStoreId = $storeId;

                return $this->rows;
            }
        };

        $result = $mapper->map(
            [
                10 => ['sku' => 'p-10'],
                20 => ['sku' => 'p-20'],
            ],
            3
        );

        self::assertSame([10, 20], $mapper->capturedProductIds);
        self::assertSame(3, $mapper->capturedStoreId);
        self::assertSame(
            [
                10 => [
                    'category_ids' => [4, 8],
                    'position_category_4' => 12,
                    'position_category_8' => 1,
                ],
                20 => [
                    'category_ids' => [4],
                    'position_category_4' => 3,
                ],
            ],
            $result
        );
    }
}
