<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\SearchAdapter;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Meilisearch\Client;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class ConnectionManager
{
    /**
     * @var Client[]
     */
    protected array $clients = [];

    /**
     * @param LoggerInterface $logger
     * @param ServerSettings $serverSettings
     * @param HttpFactory $httpFactory
     * @param HttpClient $httpClient
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ServerSettings $serverSettings,
        private readonly HttpFactory $httpFactory,
        private readonly HttpClient $httpClient
    ) {
    }

    /**
     * @param bool $master
     * @return Client|null
     * @throws \Exception
     */
    public function getConnection(bool $master = false): ?Client
    {
        if (!isset($this->clients[(int) $master])) {
            $this->clients[(int) $master] = $this->connect($master);
        }

        return $this->clients[(int) $master];
    }

    /**
     * @param bool $master
     * @return Client
     * @throws \Exception
     */
    private function connect(bool $master): Client
    {
        $apiKey = $master ? $this->serverSettings->getMasterKey() : $this->serverSettings->getServerSettingsApiKey();

        try {
            return new Client(
                $this->serverSettings->getServerSettingsAddress(),
                $apiKey,
                $this->httpClient,
                $this->httpFactory,
                [],
                $this->httpFactory
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \RuntimeException('Meilisearch client is not set.');
        }
    }
}
