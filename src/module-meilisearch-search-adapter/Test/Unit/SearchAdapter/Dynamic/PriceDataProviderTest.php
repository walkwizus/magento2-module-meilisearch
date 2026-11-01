<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Test\Unit\SearchAdapter\Dynamic;

use Magento\Catalog\Model\Layer\Filter\Price\Range;
use Magento\Framework\Search\Dynamic\EntityStorage;
use Magento\Framework\Search\Request\Aggregation\DynamicBucket;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchSearchAdapter\SearchAdapter\Dynamic\PriceDataProvider;

class PriceDataProviderTest extends TestCase
{
    public function testBucketsAndStatsComeFromThePriceFacet(): void
    {
        $provider = new PriceDataProvider($this->createMock(Range::class), ['12.5' => 2, '19.99' => 1, '20' => 3, '45' => 1]);
        $bucket = new DynamicBucket('price_bucket', 'price', 'manual');
        $storage = new EntityStorage([]);

        self::assertSame(['count' => 7, 'max' => 45.0, 'min' => 12.5, 'std' => 0], $provider->getAggregations($storage));
        // ranges of 10: 10-20 holds 12.5 and 19.99, 20-30 holds 20, 40-50 holds 45
        self::assertSame([2 => 3, 3 => 3, 5 => 1], $provider->getAggregation($bucket, [], 10, $storage));
        self::assertSame(
            [
                ['from' => 0, 'to' => 10, 'count' => 4],
                ['from' => 10, 'to' => 20, 'count' => 2],
            ],
            $provider->prepareData(10, [1 => 4, 2 => 2])
        );
    }

    public function testImprovedAlgorithmIsRefused(): void
    {
        $provider = new PriceDataProvider($this->createMock(Range::class), []);

        $this->expectException(\BadMethodCallException::class);
        $provider->getInterval(new DynamicBucket('price_bucket', 'price', 'improved'), [], new EntityStorage([]));
    }
}
