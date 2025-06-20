<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const CATALOG_FRONTEND_GRID_PER_PAGE_VALUES = 'catalog/frontend/grid_per_page_values';
    public const CATALOG_FRONTEND_GRID_PER_PAGE = 'catalog/frontend/grid_per_page';
    public const CATALOG_FRONTEND_LIST_PER_PAGE_VALUES = 'catalog/frontend/list_per_page_values';
    public const CATALOG_FRONTEND_LIST_PER_PAGE = 'catalog/frontend/list_per_page';
    public const XML_PATH_PRODUCT_URL_SUFFIX = 'catalog/seo/product_url_suffix';
    public const XML_PATH_PRODUCT_USE_CATEGORIES = 'catalog/seo/product_use_categories';

    /**
     * @param $storeId
     * @return mixed
     */
    public function getGridPerPageValues($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::CATALOG_FRONTEND_GRID_PER_PAGE_VALUES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getGridPerPage($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::CATALOG_FRONTEND_GRID_PER_PAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getListPerPageValues($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::CATALOG_FRONTEND_LIST_PER_PAGE_VALUES, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getListPerPage($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::CATALOG_FRONTEND_LIST_PER_PAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

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
