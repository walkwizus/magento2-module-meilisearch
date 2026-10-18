<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Walkwizus\MeilisearchFrontend\Model\AttributeResolver\Attribute\PriceResolver;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

class ProductRender implements ArgumentInterface
{
    private const PRICE_TYPE_DEFAULT = 'default';

    private const PRICE_TYPES = ['configurable', 'bundle', 'grouped'];

    /**
     * @param ConfigProvider $configProvider
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly PriceCurrencyInterface $priceCurrency
    ) { }

    /**
     * @param array $hit
     * @param array|null $childPrice
     * @return array|null
     */
    public function getPriceView(array $hit, ?array $childPrice = null): ?array
    {
        $config = $this->configProvider->get();
        $priceRender = (array)($hit['price_render'] ?? []);
        $priceAttributeCode = (string)($config['priceAttributeCode'] ?? PriceResolver::DEFAULT_ATTRIBUTE);
        $groupPrices = (array)($priceRender[$priceAttributeCode] ?? $priceRender[PriceResolver::DEFAULT_ATTRIBUTE] ?? []);
        $entry = (array)($groupPrices[(string)($config['currencyCode'] ?? '')] ?? []);

        if ($entry === []) {
            return null;
        }

        $productId = (int)($hit['id'] ?? 0);
        $type = in_array($hit['type_id'] ?? null, self::PRICE_TYPES, true) ? (string)$hit['type_id'] : self::PRICE_TYPE_DEFAULT;

        if ($type === 'grouped') {
            $minPrice = $this->buildAmount($entry['min_price'] ?? null, null, '', $config);

            return $minPrice !== null ? ['type' => $type, 'productId' => $productId, 'minPrice' => $minPrice] : null;
        }

        if ($type === 'bundle') {
            $bundleView = (int)($hit['price_view'] ?? 0) === 1
                ? 'as_low_as'
                : (!empty($entry['is_range']) ? 'range' : 'single');
            $minPrice = $this->buildAmount(
                $entry['min_price'] ?? null,
                ($bundleView === 'single' ? 'product-price-' : 'from-') . $productId,
                $bundleView === 'range' ? 'minPrice' : '',
                $config
            );

            if ($minPrice === null) {
                return null;
            }

            return [
                'type' => $type,
                'productId' => $productId,
                'bundleView' => $bundleView,
                'minPrice' => $minPrice,
                'maxPrice' => $bundleView === 'range'
                    ? $this->buildAmount($entry['max_price'] ?? null, 'to-' . $productId, 'maxPrice', $config)
                    : null,
            ];
        }

        $source = $childPrice ?? $entry;
        $finalPrice = $this->buildAmount(
            $type === 'configurable' && $childPrice === null
                ? ($entry['min_price'] ?? $entry['final_price'] ?? null)
                : ($source['final_price'] ?? null),
            'product-price-' . $productId,
            'finalPrice',
            $config
        );

        if ($finalPrice === null) {
            return null;
        }

        $oldPrice = !empty($source['has_discount'])
            ? $this->buildAmount($source['price'] ?? null, 'old-price-' . $productId, 'oldPrice', $config)
            : null;

        return [
            'type' => $type,
            'productId' => $productId,
            'isRange' => $type === 'configurable' && !empty($entry['is_range']),
            'finalPrice' => $finalPrice,
            'oldPrice' => $oldPrice,
            'hasSpecialPrice' => $oldPrice !== null && ($type !== 'configurable' || $childPrice !== null),
            'minimalPrice' => $type === self::PRICE_TYPE_DEFAULT && $childPrice === null
                ? $this->buildAmount($entry['tier_price'] ?? null, (string)$productId, '', $config)
                : null,
        ];
    }

    /**
     * @param array|null $price
     * @param string|null $priceId
     * @param string $priceType
     * @param array $config
     * @return array|null
     */
    private function buildAmount(?array $price, ?string $priceId, string $priceType, array $config): ?array
    {
        if (!is_numeric($price['excl_tax'] ?? null) || !is_numeric($price['incl_tax'] ?? null)) {
            return null;
        }

        $displayMode = (string)($config['priceDisplayMode'] ?? 'excl_tax');
        $showBoth = $displayMode === 'both';
        $value = (float)$price[$displayMode === 'excl_tax' ? 'excl_tax' : 'incl_tax'];

        return [
            'id' => $priceId !== null ? ($showBoth ? 'price-including-tax-' . $priceId : $priceId) : null,
            'type' => $priceType,
            'value' => $value,
            'price' => (string)$this->priceCurrency->format($value, false),
            'exclTax' => $showBoth ? [
                'id' => $priceId !== null ? 'price-excluding-tax-' . $priceId : null,
                'type' => $this->getBasePriceType($priceType),
                'value' => (float)$price['excl_tax'],
                'price' => (string)$this->priceCurrency->format((float)$price['excl_tax'], false),
            ] : null,
        ];
    }

    /**
     * @param string $priceType
     * @return string
     */
    private function getBasePriceType(string $priceType): string
    {
        if ($priceType === '') {
            return '';
        }

        return $priceType === 'finalPrice' ? 'basePrice' : 'base' . ucfirst($priceType);
    }
}
