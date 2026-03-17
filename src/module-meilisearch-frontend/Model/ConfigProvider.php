<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class ConfigProvider
{
    /**
     * @var array|null
     */
    private ?array $configCache = null;

    /**
     * @param ServerSettings $serverSettings
     * @param array $providers
     */
    public function __construct(
        private readonly ServerSettings $serverSettings,
        private readonly array $providers = []
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        if ($this->configCache === null) {
            $config = [];

            foreach ($this->providers as $provider) {
                if ($provider instanceof ConfigProviderInterface) {
                    foreach ($provider->get() as $key => $value) {
                        $config[$key] = $value;
                    }
                }
            }
            $config['catalog_list_mode'] = (bool)$this->serverSettings->getCatalogListMode();
            $this->configCache = $config;
        }

        return $this->configCache;
    }
}
