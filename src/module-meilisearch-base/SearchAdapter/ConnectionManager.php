<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\SearchAdapter;

use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Helper\ServerSettings;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
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
     * @param HttpClient $httpClient
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ServerSettings $serverSettings,
        private readonly HttpClient $httpClient
    ) { }

    /**
     * @return Client|null
     */
    public function getConnection(): ?Client
    {
        if (!$this->client) {
            $this->connect();
        }

        return $this->client;
    }

    /**
     * @return void
     */
    private function connect(): void
    {
        try {
            $httpFactory = new HttpFactory();
            $this->client = new Client(
                $this->serverSettings->getServerSettingsAddress(),
                $this->serverSettings->getServerSettingsApiKey(),
                $this->httpClient,
                $httpFactory,
                [],
                $httpFactory
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new \RuntimeException('Meilisearch client is not set.');
        }
    }
}
