<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Test\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchIndices\Service\IndexStoreResolver;

class IndexStoreResolverTest extends TestCase
{
    #[DataProvider('resolveProvider')]
    public function testResolveReturnsTheLastUnderscoreSeparatedSegmentAsInteger(
        string $indexName,
        int $expectedStoreId
    ): void {
        $resolver = new IndexStoreResolver();

        self::assertSame($expectedStoreId, $resolver->resolve($indexName));
    }

    public static function resolveProvider(): array
    {
        return [
            'standard index' => ['catalog_product_4', 4],
            'prefixed index' => ['my_prefix_catalog_product_12', 12],
            'non integer suffix' => ['catalog_product_store', 0],
        ];
    }
}
