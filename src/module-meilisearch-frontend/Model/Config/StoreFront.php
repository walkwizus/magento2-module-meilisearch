<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class StoreFront
{
    public const CATALOG_FRONTEND_LIST_MODE = 'catalog/frontend/list_mode';
    public const CATALOG_FRONTEND_GRID_PER_PAGE_VALUES = 'catalog/frontend/grid_per_page_values';
    public const CATALOG_FRONTEND_GRID_PER_PAGE = 'catalog/frontend/grid_per_page';
    public const CATALOG_FRONTEND_LIST_PER_PAGE_VALUES = 'catalog/frontend/list_per_page_values';
    public const CATALOG_FRONTEND_LIST_PER_PAGE = 'catalog/frontend/list_per_page';
    public const CATALOG_FRONTEND_LIST_ALLOW_ALL = 'catalog/frontend/list_allow_all';

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
    public function getListMode($storeId): mixed
    {
        return $this->scopeConfig->getValue(self::CATALOG_FRONTEND_LIST_MODE, ScopeInterface::SCOPE_STORE, $storeId);
    }

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
    public function getListAllowAll($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::CATALOG_FRONTEND_LIST_ALLOW_ALL, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
