<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchBase\Model\AttributeMapper;

class AttributeMapperTest extends TestCase
{
    public function testMapMergesMappersAndAppliesPostMappers(): void
    {
        $documentData = [
            10 => ['existing' => 'first'],
            20 => ['existing' => 'second'],
        ];
        $context = ['mode' => 'full'];

        $firstMapper = $this->createMock(AttributeMapperInterface::class);
        $firstMapper->expects(self::once())
            ->method('map')
            ->with($documentData, 1, $context)
            ->willReturn([
                10 => ['attr_a' => 'foo'],
                20 => ['attr_a' => 'bar'],
            ]);

        $secondMapper = $this->createMock(AttributeMapperInterface::class);
        $secondMapper->expects(self::once())
            ->method('map')
            ->with($documentData, 1, $context)
            ->willReturn([
                10 => ['attr_b' => 5],
            ]);

        $postMapper = $this->createMock(AttributeMapperInterface::class);
        $postMapper->expects(self::once())
            ->method('map')
            ->willReturnCallback(static function (array $documents, $storeId): array {
                foreach ($documents as $id => $document) {
                    $documents[$id]['post_processed'] = $storeId === 1;
                }

                return $documents;
            });

        $attributeMapper = new AttributeMapper(
            ['catalog_product' => [$firstMapper, $secondMapper]],
            ['catalog_product' => [$postMapper]]
        );

        self::assertSame(
            [
                10 => ['attr_a' => 'foo', 'attr_b' => 5, 'post_processed' => true],
                20 => ['attr_a' => 'bar', 'post_processed' => true],
            ],
            $attributeMapper->map('catalog_product', $documentData, 1, $context)
        );
    }

    public function testMapReturnsOriginalDataWhenNoMapperExists(): void
    {
        $documentData = [
            10 => ['sku' => 'sku-10'],
        ];

        $attributeMapper = new AttributeMapper();

        self::assertSame($documentData, $attributeMapper->map('catalog_product', $documentData, 1));
    }

    public function testMapThrowsWhenMapperDoesNotImplementExpectedInterface(): void
    {
        $attributeMapper = new AttributeMapper(
            ['catalog_product' => [new \stdClass()]]
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Attribute provider must implement "Walkwizus\MeilisearchBase\Api\AttributeMapperInterface".'
        );

        $attributeMapper->map('catalog_product', [1 => []], 1);
    }
}
