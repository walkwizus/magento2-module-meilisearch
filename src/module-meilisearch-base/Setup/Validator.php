<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Setup;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Magento\Search\Model\SearchEngine\ValidatorInterface;
use Meilisearch\ClientFactory;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class Validator implements ValidatorInterface
{
    /**
     * @param ServerSettings $serverSettings
     * @param ClientFactory $clientFactory
     * @param HttpFactory $httpFactory
     * @param HttpClient $httpClient
     */
    public function __construct(
        private readonly ServerSettings $serverSettings,
        private readonly ClientFactory $clientFactory,
        private readonly HttpFactory $httpFactory,
        private readonly HttpClient $httpClient
    ) { }

    /**
     * @return array
     */
    public function validate(): array
    {
        $address = $this->serverSettings->getServerSettingsAddress();
        if ($address === '') {
            return [
                'Meilisearch server address is not configured.'
                . ' Use --meilisearch-address or set "' . ServerSettings::MEILISEARCH_SERVER_SETTINGS_ADDRESS . '".'
            ];
        }

        try {
            $client = $this->clientFactory->create([
                'url' => $address,
                'apiKey' => $this->serverSettings->getServerSettingsApiKey(),
                'httpClient' => $this->httpClient,
                'requestFactory' => $this->httpFactory,
                'streamFactory' => $this->httpFactory
            ]);
        } catch (\Exception $e) {
            return ['Could not create the Meilisearch client: ' . $e->getMessage()];
        }

        if (!$client->isHealthy()) {
            return ["Could not connect to Meilisearch at \"{$address}\". Verify that the server is running."];
        }

        try {
            $client->stats();
        } catch (\Exception $e) {
            return [
                'Meilisearch is reachable but the admin API key was rejected: ' . $e->getMessage()
                . ' Use --meilisearch-api-key, or --meilisearch-master-key to retrieve the default keys.'
            ];
        }

        return [];
    }
}
