<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;

class ConfigProvider
{
    /**
     * @var array|null
     */
    private ?array $configCache = null;

    /**
     * @param ConfigProviderInterface[] $providers
     */
    public function __construct(
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

            $this->configCache = $config;
        }

        return $this->configCache;
    }
}
