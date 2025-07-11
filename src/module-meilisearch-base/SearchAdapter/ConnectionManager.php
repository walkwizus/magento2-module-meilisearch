<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\SearchAdapter;

use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Client as HttpClient;
use Meilisearch\Client;

class ConnectionManager
{
    /**
     * @var Client|null
     */
    protected ?Client $client = null;

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
    ) { }

    /**
     * @param bool $master
     * @return Client|null
     * @throws \Exception
     */
    public function getConnection(bool $master = false): ?Client
    {
        if (!$this->client) {
            $this->connect($master);
        }

        return $this->client;
    }

    /**
     * @param bool $master
     * @return void
     * @throws \Exception
     */
    private function connect(bool $master): void
    {
        $apiKey = $master ? $this->serverSettings->getMasterKey() : $this->serverSettings->getServerSettingsApiKey();

        try {
            $this->client = new Client(
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
