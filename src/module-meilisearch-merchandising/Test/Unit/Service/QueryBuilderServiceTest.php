<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Test\Unit\Service;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Swatches\Helper\Data as SwatchHelper;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Walkwizus\MeilisearchMerchandising\Model\AttributeRuleProvider;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;

class QueryBuilderServiceTest extends TestCase
{
    public function testConvertRulesToMeilisearchQueryBuildsNestedQuery(): void
    {
        $attributeResolver = $this->createMock(AttributeResolver::class);
        $attributeResolver->method('resolve')->willReturnCallback(
            static fn(string $field): string => $field === 'brand' ? 'brand_keyword' : $field
        );

        $service = new QueryBuilderService(
            $this->createMock(AttributeRuleProvider::class),
            $this->createMock(AttributeRepositoryInterface::class),
            $attributeResolver,
            $this->createMock(SwatchHelper::class)
        );

        $query = $service->convertRulesToMeilisearchQuery([
            'condition' => 'OR',
            'rules' => [
                [
                    'field' => 'brand',
                    'operator' => 'equal',
                    'value' => 'Nike',
                    'type' => 'string',
                ],
                [
                    'field' => 'price',
                    'operator' => 'greater_or_equal',
                    'value' => 100,
                    'type' => 'double',
                ],
            ],
        ]);

        self::assertSame('(brand_keyword = "Nike") OR (price >= 100)', $query);
    }

    public function testIsMatchReturnsTrueForNestedAndRules(): void
    {
        $attributeResolver = $this->createMock(AttributeResolver::class);
        $attributeResolver->method('resolve')->willReturnArgument(0);

        $service = new QueryBuilderService(
            $this->createMock(AttributeRuleProvider::class),
            $this->createMock(AttributeRepositoryInterface::class),
            $attributeResolver,
            $this->createMock(SwatchHelper::class)
        );

        $result = $service->isMatch(
            [
                'brand' => 'Nike',
                'tags' => ['new', 'sale'],
                'qty' => 5,
            ],
            [
                'condition' => 'AND',
                'rules' => [
                    [
                        'field' => 'brand',
                        'operator' => 'equal',
                        'value' => 'Nike',
                    ],
                    [
                        'field' => 'tags',
                        'operator' => 'in',
                        'value' => ['sale'],
                    ],
                    [
                        'field' => 'qty',
                        'operator' => 'greater',
                        'value' => 3,
                    ],
                ],
            ]
        );

        self::assertTrue($result);
    }

    public function testIsMatchSupportsSwatchLikeValuesWithMetadataSuffix(): void
    {
        $attributeResolver = $this->createMock(AttributeResolver::class);
        $attributeResolver->method('resolve')->willReturnArgument(0);

        $service = new QueryBuilderService(
            $this->createMock(AttributeRuleProvider::class),
            $this->createMock(AttributeRepositoryInterface::class),
            $attributeResolver,
            $this->createMock(SwatchHelper::class)
        );

        $result = $service->isMatch(
            ['color' => 'Black'],
            [
                'field' => 'color',
                'operator' => 'equal',
                'value' => 'Black|1|#000000',
            ]
        );

        self::assertTrue($result);
    }
}
