<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryFacet;

use Walkwizus\MeilisearchMerchandising\Api\CategoryFacetOrderingResolverInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryFacet;

class OrderingResolver implements CategoryFacetOrderingResolverInterface
{
    /**
     * @var array
     */
    private array $resolved = [];

    /**
     * @param CategoryFacet $categoryFacetResource
     * @param OrderingNormalizer $orderingNormalizer
     */
    public function __construct(
        private readonly CategoryFacet $categoryFacetResource,
        private readonly OrderingNormalizer $orderingNormalizer
    ) { }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @return array|null
     */
    public function resolve(int $storeId, int $categoryId): ?array
    {
        $key = $storeId . '_' . $categoryId;

        if (!array_key_exists($key, $this->resolved)) {
            $ordering = $this->categoryFacetResource->getOrderings($storeId, [$categoryId])[$categoryId] ?? null;
            $this->resolved[$key] = $ordering !== null ? $this->orderingNormalizer->normalize($ordering) : null;
        }

        return $this->resolved[$key];
    }
}
