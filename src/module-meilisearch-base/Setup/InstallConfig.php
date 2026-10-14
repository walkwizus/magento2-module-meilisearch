<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Setup;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Search\Setup\InstallConfigInterface;
use Meilisearch\ClientFactory;
use Meilisearch\Endpoints\Keys;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class InstallConfig implements InstallConfigInterface
{
    const CATALOG_SEARCH_ENGINE = 'catalog/search/engine';

    const DEFAULT_ADMIN_KEY_NAME = 'Default Admin API Key';
    const DEFAULT_SEARCH_KEY_NAME = 'Default Search API Key';

    /**
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     * @param ClientFactory $clientFactory
     * @param HttpFactory $httpFactory
     * @param HttpClient $httpClient
     */
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly EncryptorInterface $encryptor,
        private readonly ClientFactory $clientFactory,
        private readonly HttpFactory $httpFactory,
        private readonly HttpClient $httpClient
    ) { }

    /**
     * @param array $inputOptions
     * @return void
     */
    public function configure(array $inputOptions)
    {
        $options = array_filter($inputOptions, fn ($value) => $value !== null && $value !== '');

        if (isset($options[SearchConfigOptionsList::INPUT_KEY_SEARCH_ENGINE])) {
            $this->configWriter->save(self::CATALOG_SEARCH_ENGINE, $options[SearchConfigOptionsList::INPUT_KEY_SEARCH_ENGINE]);
        }

        $address = $options[SearchConfigOptionsList::INPUT_KEY_MEILISEARCH_ADDRESS] ?? null;
        $masterKey = $options[SearchConfigOptionsList::INPUT_KEY_MEILISEARCH_MASTER_KEY] ?? null;
        $apiKey = $options[SearchConfigOptionsList::INPUT_KEY_MEILISEARCH_API_KEY] ?? null;
        $clientApiKey = $options[SearchConfigOptionsList::INPUT_KEY_MEILISEARCH_CLIENT_API_KEY] ?? null;
        $clientAddress = $options[SearchConfigOptionsList::INPUT_KEY_MEILISEARCH_CLIENT_ADDRESS] ?? $address;

        if ($address !== null && $masterKey !== null && ($apiKey === null || $clientApiKey === null)) {
            $defaultKeys = $this->getDefaultKeys($address, $masterKey);
            $apiKey ??= $defaultKeys[self::DEFAULT_ADMIN_KEY_NAME] ?? null;
            $clientApiKey ??= $defaultKeys[self::DEFAULT_SEARCH_KEY_NAME] ?? null;
        }

        $this->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_ADDRESS, $address);
        $this->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_MASTER_KEY, $masterKey, true);
        $this->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_API_KEY, $apiKey, true);
        $this->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_CLIENT_ADDRESS, $clientAddress);
        $this->save(ServerSettings::MEILISEARCH_SERVER_SETTINGS_CLIENT_API_KEY, $clientApiKey);
        $this->save(
            ServerSettings::MEILISEARCH_SERVER_SETTINGS_INDEXES_PREFIX,
            $options[SearchConfigOptionsList::INPUT_KEY_MEILISEARCH_INDEXES_PREFIX] ?? null
        );
    }

    /**
     * @param string $path
     * @param string|null $value
     * @param bool $encrypt
     * @return void
     */
    private function save(string $path, ?string $value, bool $encrypt = false): void
    {
        if ($value === null) {
            return;
        }

        $this->configWriter->save($path, $encrypt ? $this->encryptor->encrypt($value) : $value);
    }

    /**
     * @param string $address
     * @param string $masterKey
     * @return array
     */
    private function getDefaultKeys(string $address, string $masterKey): array
    {
        try {
            $client = $this->clientFactory->create([
                'url' => $address,
                'apiKey' => $masterKey,
                'httpClient' => $this->httpClient,
                'requestFactory' => $this->httpFactory,
                'streamFactory' => $this->httpFactory
            ]);
            $keys = [];
            /** @var Keys $key */
            foreach ($client->getKeys()->getResults() as $key) {
                $keys[(string)$key->getName()] = $key->getKey();
            }
            return $keys;
        } catch (\Exception $e) {
            return [];
        }
    }
}
