<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;

class ServerSettings
{
    const MEILISEARCH_SERVER_SETTINGS_ADDRESS = 'meilisearch_server/settings/address';
    const MEILISEARCH_SERVER_SETTINGS_MASTER_KEY = 'meilisearch_server/settings/master_key';
    const MEILISEARCH_SERVER_SETTINGS_API_KEY = 'meilisearch_server/settings/api_key';
    const MEILISEARCH_SERVER_SETTINGS_CLIENT_ADDRESS = 'meilisearch_server/settings/client_address';
    const MEILISEARCH_SERVER_SETTINGS_CLIENT_API_KEY = 'meilisearch_server/settings/client_api_key';
    const MEILISEARCH_SERVER_SETTINGS_INDEXES_PREFIX = 'meilisearch_server/settings/indexes_prefix';

    const MEILISEARCH_SERVER_SETTINGS_CATALOG_LIST_MODE = 'meilisearch_server/settings/catalog_list_mode';

    const MEILISEARCH_AJAX_FILTERS_CACHE_TAG = 'mei_s';

    const MEILISEARCH_SERVER_SIDE_CATALOG_LIST = 0;

    const MEILISEARCH_CLIENT_SIDE_CATALOG_LIST = 1;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Encryptor $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Encryptor $encryptor
    ) { }

    /**
     * @return string
     */
    public function getServerSettingsAddress(): string
    {
        return $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_ADDRESS) ?? '';
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getMasterKey(): string
    {
        $key = $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_MASTER_KEY) ?? '';
        return $this->encryptor->decrypt($key);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getServerSettingsApiKey(): string
    {
        $key = $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_API_KEY) ?? '';
        return $this->encryptor->decrypt($key);
    }

    /**
     * @return string
     */
    public function getServerSettingsClientAddress(): string
    {
        return $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_CLIENT_ADDRESS) ?? '';
    }

    /**
     * @return string
     */
    public function getServerSettingsClientApiKey(): string
    {
        return $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_CLIENT_API_KEY) ?? '';
    }

    /**
     * @return string
     */
    public function getServerSettingsIndexesPrefix(): string
    {
        return $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_INDEXES_PREFIX) ?? '';
    }

    /**
     * @return int
     */
    public function getCatalogListMode(): int
    {
        return (int) $this->scopeConfig->getValue(self::MEILISEARCH_SERVER_SETTINGS_CATALOG_LIST_MODE);
    }
}
