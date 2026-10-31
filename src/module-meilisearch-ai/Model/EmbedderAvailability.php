<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Service\SettingsManager;

class EmbedderAvailability
{
    public const CACHE_TAG = 'MEILISEARCH_AI_EMBEDDERS';

    private const CACHE_KEY_PREFIX = 'meilisearch_ai_embedders_';
    private const CACHE_LIFETIME = 300;

    /**
     * @var array
     */
    private array $embedders = [];

    /**
     * @param SettingsManager $settingsManager
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param string $indexUid
     * @param string $identifier
     * @return bool
     */
    public function isAvailable(string $indexUid, string $identifier): bool
    {
        return $identifier !== '' && in_array($identifier, $this->getIdentifiers($indexUid), true);
    }

    /**
     * @return void
     */
    public function clean(): void
    {
        $this->embedders = [];
        $this->cache->clean([self::CACHE_TAG]);
    }

    /**
     * @param string $indexUid
     * @return array
     */
    private function getIdentifiers(string $indexUid): array
    {
        if (isset($this->embedders[$indexUid])) {
            return $this->embedders[$indexUid];
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $indexUid;
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return $this->embedders[$indexUid] = (array) json_decode((string) $cached, true);
        }

        try {
            $identifiers = array_keys((array) $this->settingsManager->getEmbedders($indexUid));
        } catch (\Exception $e) {
            $this->logger->warning('Meilisearch: unable to read embedders of index ' . $indexUid, ['exception' => $e]);

            return $this->embedders[$indexUid] = [];
        }

        $this->cache->save((string) json_encode($identifiers), $cacheKey, [self::CACHE_TAG], self::CACHE_LIFETIME);

        return $this->embedders[$indexUid] = $identifiers;
    }
}
