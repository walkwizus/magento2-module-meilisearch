<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\SearchAdapter\Dynamic;

use Magento\Catalog\Model\Layer\Filter\Price\Range;
use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Framework\Search\Dynamic\EntityStorage;
use Magento\Framework\Search\Dynamic\IntervalInterface;
use Magento\Framework\Search\Request\BucketInterface;

/**
 * Buckets prices from the search's own price facet, with no further queries.
 */
class PriceDataProvider implements DataProviderInterface
{
    /**
     * @param Range $range
     * @param array<string, int> $prices
     */
    public function __construct(
        private readonly Range $range,
        private readonly array $prices
    ) { }

    /**
     * @return int
     */
    public function getRange()
    {
        // @phpstan-ignore return.type (core documents getPriceRange() as array; it returns the category's price step)
        return $this->range->getPriceRange();
    }

    /**
     * @param EntityStorage $entityStorage
     * @return array
     */
    public function getAggregations(EntityStorage $entityStorage)
    {
        $prices = array_map('floatval', array_keys($this->prices));

        return [
            'count' => array_sum($this->prices),
            'max' => $prices ? max($prices) : 0,
            'min' => $prices ? min($prices) : 0,
            'std' => 0, // only the improved algorithm reads it, and that is not supported
        ];
    }

    /**
     * @param BucketInterface $bucket
     * @param array $dimensions
     * @param EntityStorage $entityStorage
     * @return IntervalInterface
     * @throws \BadMethodCallException
     */
    public function getInterval(BucketInterface $bucket, array $dimensions, EntityStorage $entityStorage)
    {
        throw new \BadMethodCallException('The improved price algorithm is not supported with Meilisearch.');
    }

    /**
     * @param BucketInterface $bucket
     * @param array $dimensions
     * @param int $range
     * @param EntityStorage $entityStorage
     * @return array
     */
    public function getAggregation(BucketInterface $bucket, array $dimensions, $range, EntityStorage $entityStorage)
    {
        $result = [];
        foreach ($this->prices as $price => $count) {
            $key = (int)floor((float)$price / $range) + 1;
            $result[$key] = ($result[$key] ?? 0) + $count;
        }
        ksort($result);

        return $result;
    }

    /**
     * @param int $range
     * @param array $dbRanges
     * @return array
     */
    public function prepareData($range, array $dbRanges)
    {
        $data = [];
        foreach ($dbRanges as $index => $count) {
            $data[] = [
                'from' => $index == 1 ? 0 : ($index - 1) * $range,
                'to' => $index * $range,
                'count' => $count,
            ];
        }

        return $data;
    }
}
