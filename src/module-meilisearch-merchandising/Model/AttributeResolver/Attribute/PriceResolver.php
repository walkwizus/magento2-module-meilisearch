<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributeResolver\Attribute;

use Walkwizus\MeilisearchBase\Api\AttributeResolverInterface;

class PriceResolver implements AttributeResolverInterface
{
    /**
     * @return string
     */
    public function resolve(): string
    {
        return 'price_0';
    }
}
