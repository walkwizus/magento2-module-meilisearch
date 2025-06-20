<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Model\AttributeResolver\Attribute\PriceResolver;
use Magento\Framework\Locale\Format;

class PriceConfigProvider implements ConfigProviderInterface
{
    /**
     * @param PriceResolver $priceResolver
     * @param Format $format
     */
    public function __construct(
        private readonly PriceResolver $priceResolver,
        private readonly Format $format
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        return [
            'priceAttributeCode' => $this->priceResolver->resolve(),
            'priceFormat' => $this->format->getPriceFormat()
        ];
    }
}
