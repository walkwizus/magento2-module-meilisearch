<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\Tax;

use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Config as TaxConfig;

class ProductPriceCalculator
{
    private const ITEM_CODE = 'meilisearch_price';

    private const ITEM_TYPE = 'product';

    private const PRECISION = 10;

    /**
     * @var array<string, array{excl_tax: float, incl_tax: float}>
     */
    private array $prices = [];

    /**
     * @param TaxCalculationInterface $taxCalculation
     * @param QuoteDetailsInterfaceFactory $quoteDetailsFactory
     * @param QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory
     * @param TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param TaxConfig $taxConfig
     */
    public function __construct(
        private readonly TaxCalculationInterface $taxCalculation,
        private readonly QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        private readonly QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        private readonly TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        private readonly TaxConfig $taxConfig
    ) { }

    /**
     * @param float $amount
     * @param int $productTaxClassId
     * @param int $customerTaxClassId
     * @param int $storeId
     * @return array{excl_tax: float, incl_tax: float}
     */
    public function calculate(float $amount, int $productTaxClassId, int $customerTaxClassId, int $storeId): array
    {
        $key = $storeId . '|' . $productTaxClassId . '|' . $customerTaxClassId . '|' . $amount;

        return $this->prices[$key] ??= $this->computePrices($amount, $productTaxClassId, $customerTaxClassId, $storeId);
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->prices = [];
    }

    /**
     * @param float $amount
     * @param int $productTaxClassId
     * @param int $customerTaxClassId
     * @param int $storeId
     * @return array{excl_tax: float, incl_tax: float}
     */
    private function computePrices(float $amount, int $productTaxClassId, int $customerTaxClassId, int $storeId): array
    {
        if ($amount == 0.0 || !$this->taxConfig->needPriceConversion($storeId)) {
            $price = round($amount, self::PRECISION);

            return ['excl_tax' => $price, 'incl_tax' => $price];
        }

        $item = $this->quoteDetailsItemFactory->create()
            ->setQuantity(1)
            ->setCode(self::ITEM_CODE)
            ->setType(self::ITEM_TYPE)
            ->setTaxClassKey($this->createTaxClassKey($productTaxClassId))
            ->setIsTaxIncluded($this->taxConfig->priceIncludesTax($storeId))
            ->setUnitPrice($amount);

        $quoteDetails = $this->quoteDetailsFactory->create()
            ->setCustomerTaxClassKey($this->createTaxClassKey($customerTaxClassId))
            ->setItems([$item]);

        $items = $this->taxCalculation->calculateTax($quoteDetails, $storeId, false)->getItems();
        $taxDetailsItem = reset($items);

        return [
            'excl_tax' => round((float) $taxDetailsItem->getPrice(), self::PRECISION),
            'incl_tax' => round((float) $taxDetailsItem->getPriceInclTax(), self::PRECISION),
        ];
    }

    /**
     * @param int $taxClassId
     * @return TaxClassKeyInterface
     */
    private function createTaxClassKey(int $taxClassId): TaxClassKeyInterface
    {
        return $this->taxClassKeyFactory->create()
            ->setType(TaxClassKeyInterface::TYPE_ID)
            ->setValue((string) $taxClassId);
    }
}
