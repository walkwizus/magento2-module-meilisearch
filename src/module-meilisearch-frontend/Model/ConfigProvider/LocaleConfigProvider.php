<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;

class LocaleConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) { }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $localeCode = (string) $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE
        );

        return [
            'locale'       => str_replace('_', '-', $localeCode),
            'currencyCode' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
        ];
    }
}
