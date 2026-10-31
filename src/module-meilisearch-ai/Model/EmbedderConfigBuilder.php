<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory as EmbedderCollectionFactory;

class EmbedderConfigBuilder
{
    /**
     * @param EmbedderCollectionFactory $embedderCollectionFactory
     * @param ApiKeyEncryption $apiKeyEncryption
     */
    public function __construct(
        private readonly EmbedderCollectionFactory $embedderCollectionFactory,
        private readonly ApiKeyEncryption $apiKeyEncryption
    ) {
    }

    /**
     * @param array $embedderIds
     * @return array
     */
    public function build(array $embedderIds): array
    {
        $embedderIds = array_values(array_filter(array_map('intval', $embedderIds)));

        if ($embedderIds === []) {
            return [];
        }

        $collection = $this->embedderCollectionFactory->create()
            ->addFieldToFilter(EmbedderInterface::EMBEDDER_ID, ['in' => $embedderIds]);

        $config = [];

        /** @var EmbedderInterface $embedder */
        foreach ($collection as $embedder) {
            $config[(string) $embedder->getIdentifier()] = array_filter([
                'source' => $embedder->getSource(),
                'model' => $embedder->getModel(),
                'apiKey' => $this->decryptApiKey($embedder->getApiKey()),
                'url' => $embedder->getUrl(),
                'documentTemplate' => $embedder->getDocumentTemplate()
            ], static fn ($value): bool => $value !== null && $value !== '');
        }

        ksort($config);

        return $config;
    }

    /**
     * @param array $config
     * @return string
     */
    public function hash(array $config): string
    {
        return hash('sha256', (string) json_encode($config));
    }

    /**
     * @param string|null $apiKey
     * @return string|null
     */
    private function decryptApiKey(?string $apiKey): ?string
    {
        if ($apiKey === null || $apiKey === '') {
            return null;
        }

        return $this->apiKeyEncryption->decrypt($apiKey);
    }
}
