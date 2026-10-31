<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;

class ProductActionsConfigProvider implements ConfigProviderInterface
{
    /**
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ModuleManager $moduleManager
    ) {
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return [
            'productActions' => [
                'addToCartUrl' => $this->urlBuilder->getUrl('checkout/cart/add'),
                'wishlistEnabled' => $this->isWishlistEnabled(),
                'addToWishlistUrl' => $this->urlBuilder->getUrl('wishlist/index/add'),
                'addToCompareUrl' => $this->urlBuilder->getUrl('catalog/product_compare/add'),
            ],
        ];
    }

    /**
     * @return bool
     */
    private function isWishlistEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Magento_Wishlist')
            && $this->scopeConfig->isSetFlag('wishlist/general/active', ScopeInterface::SCOPE_STORE);
    }
}
