<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\AttributeResolver\Attribute;

use Walkwizus\MeilisearchBase\Api\AttributeResolverInterface;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;

class PositionResolver implements AttributeResolverInterface
{
    const ATTRIBUTE_PREFIX = 'position_';

    /**
     * @param LayerResolver $layerResolver
     */
    public function __construct(
        private readonly LayerResolver $layerResolver
    ) { }

    /**
     * @return string
     */
    public function resolve(): string
    {
        $currentCategoryId = $this->layerResolver->get()->getCurrentCategory()->getId();
        return self::ATTRIBUTE_PREFIX . 'category_' . $currentCategoryId;
    }
}
