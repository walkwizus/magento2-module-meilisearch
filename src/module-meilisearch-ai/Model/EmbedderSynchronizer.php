<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Meilisearch\Contracts\Task;
use Meilisearch\Exceptions\ApiException;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchBase\Service\SettingsManager;

class EmbedderSynchronizer
{
    private const FLAG_PREFIX = 'meilisearch_ai_embedders_';

    /**
     * @param LinkResource $linkResource
     * @param EmbedderConfigBuilder $configBuilder
     * @param SettingsManager $settingsManager
     * @param EmbedderAvailability $embedderAvailability
     * @param FlagManager $flagManager
     */
    public function __construct(
        private readonly LinkResource $linkResource,
        private readonly EmbedderConfigBuilder $configBuilder,
        private readonly SettingsManager $settingsManager,
        private readonly EmbedderAvailability $embedderAvailability,
        private readonly FlagManager $flagManager
    ) {
    }

    /**
     * @param string $indexUid
     * @param string|null $targetIndexUid
     * @return Task|null
     * @throws ApiException
     * @throws LocalizedException
     */
    public function sync(string $indexUid, ?string $targetIndexUid = null): ?Task
    {
        $targetIndexUid ??= $indexUid;
        $config = $this->configBuilder->build($this->linkResource->getEmbedderIdsByUid($indexUid));
        $hash = $this->configBuilder->hash($config);
        $current = $this->getCurrentEmbedders($targetIndexUid);

        if ($this->isUpToDate($targetIndexUid, $hash, $config, $current)) {
            return null;
        }

        if ($config === [] && $current === []) {
            $task = null;
        } elseif ($config === []) {
            $task = $this->settingsManager->resetEmbedders($targetIndexUid);
        } else {
            foreach (array_diff(array_keys($current), array_keys($config)) as $staleIdentifier) {
                $config[$staleIdentifier] = null;
            }

            $task = $this->settingsManager->updateEmbedders($targetIndexUid, $config);
        }

        $this->flagManager->saveFlag(self::FLAG_PREFIX . $targetIndexUid, $hash);
        $this->embedderAvailability->clean();

        return $task;
    }

    /**
     * @param string $fromIndexUid
     * @param string $toIndexUid
     * @return void
     */
    public function moveState(string $fromIndexUid, string $toIndexUid): void
    {
        $hash = $this->flagManager->getFlagData(self::FLAG_PREFIX . $fromIndexUid);

        if ($hash !== null) {
            $this->flagManager->saveFlag(self::FLAG_PREFIX . $toIndexUid, $hash);
            $this->flagManager->deleteFlag(self::FLAG_PREFIX . $fromIndexUid);
        }

        $this->embedderAvailability->clean();
    }

    /**
     * @param string $indexUid
     * @return bool
     * @throws LocalizedException
     */
    public function hasEmbedders(string $indexUid): bool
    {
        return $this->linkResource->getEmbedderIdsByUid($indexUid) !== [];
    }

    /**
     * @param string $targetIndexUid
     * @param string $hash
     * @param array $config
     * @param array $current
     * @return bool
     */
    private function isUpToDate(string $targetIndexUid, string $hash, array $config, array $current): bool
    {
        if (array_keys($current) != array_keys($config)) {
            return false;
        }

        return $this->flagManager->getFlagData(self::FLAG_PREFIX . $targetIndexUid) === $hash;
    }

    /**
     * @param string $indexUid
     * @return array
     * @throws ApiException
     */
    private function getCurrentEmbedders(string $indexUid): array
    {
        try {
            $embedders = (array) $this->settingsManager->getEmbedders($indexUid);
        } catch (ApiException $e) {
            if ($e->httpStatus === 404) {
                return [];
            }

            throw $e;
        }

        ksort($embedders);

        return $embedders;
    }
}
