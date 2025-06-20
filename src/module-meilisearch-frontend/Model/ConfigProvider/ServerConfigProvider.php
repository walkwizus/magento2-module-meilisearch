<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchBase\Helper\ServerSettings;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Exception\NoSuchEntityException;

class ServerConfigProvider implements ConfigProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param ServerSettings $serverSettings
     * @param SearchIndexNameResolver $searchIndexNameResolver
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ServerSettings $serverSettings,
        private readonly SearchIndexNameResolver $searchIndexNameResolver
    ) { }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = $this->storeManager->getStore()->getId();

        return [
            'host' => $this->serverSettings->getServerSettingsClientAddress(),
            'apiKey' => $this->serverSettings->getServerSettingsClientApiKey(),
            'indexName' => $this->searchIndexNameResolver->getIndexName($storeId),
        ];
    }
}
