<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SearchEngineOptimization
{
    public const XML_PATH_PRODUCT_URL_SUFFIX = 'catalog/seo/product_url_suffix';
    public const XML_PATH_PRODUCT_USE_CATEGORIES = 'catalog/seo/product_use_categories';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) { }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getProductUrlSuffix($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_URL_SUFFIX, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getProductUseCategories($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCT_USE_CATEGORIES, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
