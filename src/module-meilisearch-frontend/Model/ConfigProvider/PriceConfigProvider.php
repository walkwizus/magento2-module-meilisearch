<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Model\AttributeResolver\Attribute\PriceResolver;
use Magento\Framework\Locale\Format;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Store\Model\Store;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class PriceConfigProvider implements ConfigProviderInterface
{
    /**
     * @param PriceResolver $priceResolver
     * @param Format $format
     * @param StoreManagerInterface $storeManager
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        private readonly PriceResolver $priceResolver,
        private readonly Format $format,
        private readonly StoreManagerInterface $storeManager,
        private readonly TaxConfig $taxConfig
    ) { }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        return [
            'priceAttributeCode' => $this->priceResolver->resolve(),
            'priceFormat' => $this->format->getPriceFormat(),
            'currencyCode' => $this->getStore()->getCurrentCurrency()->getCode(),
            'baseCurrencyCode' => $this->getStore()->getBaseCurrencyCode(),
            'priceDisplayMode' => $this->getPriceDisplayMode()
        ];
    }

    /**
     * @return Store
     * @throws NoSuchEntityException
     */
    private function getStore(): Store
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store;
    }

    /**
     * @return string
     */
    private function getPriceDisplayMode(): string
    {
        return match ((int) $this->taxConfig->getPriceDisplayType()) {
            TaxConfig::DISPLAY_TYPE_INCLUDING_TAX => 'incl_tax',
            TaxConfig::DISPLAY_TYPE_BOTH => 'both',
            default => 'excl_tax',
        };
    }
}
