<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\AttributeResolver\Attribute;

use Magento\Customer\Model\Session as CustomerSession;
use Walkwizus\MeilisearchBase\Api\AttributeResolverInterface;

class PriceResolver implements AttributeResolverInterface
{
    const ATTRIBUTE_PREFIX = 'price_';
    const DEFAULT_ATTRIBUTE = 'price_0';

    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private readonly CustomerSession $customerSession
    ) { }

    /**
     * @return string
     */
    public function resolve(): string
    {
        try {
            return self::ATTRIBUTE_PREFIX . $this->customerSession->getCustomerGroupId();
        } catch (\Exception $e) {
            return self::DEFAULT_ATTRIBUTE;
        }
    }
}
