<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\ConfigProvider;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchAi\Api\EmbedderRepositoryInterface;
use Walkwizus\MeilisearchAi\Model\Config\VectorSettings;
use Walkwizus\MeilisearchAi\Model\EmbedderAvailability;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;

class HybridConfigProvider implements ConfigProviderInterface
{
    /**
     * @param VectorSettings $vectorSettings
     * @param SearchIndexNameResolver $indexNameResolver
     * @param StoreManagerInterface $storeManager
     * @param EmbedderRepositoryInterface $embedderRepository
     * @param LinkResource $linkResource
     * @param EmbedderAvailability $embedderAvailability
     */
    public function __construct(
        private readonly VectorSettings $vectorSettings,
        private readonly SearchIndexNameResolver $indexNameResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly EmbedderRepositoryInterface $embedderRepository,
        private readonly LinkResource $linkResource,
        private readonly EmbedderAvailability $embedderAvailability
    ) {
    }

    /**
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $indexUid = $this->indexNameResolver->getIndexName($storeId, 'catalog_product');

        $settings = $this->vectorSettings->getVectorSettings($indexUid);
        $embedderIdentifier = $settings['is_vector_enabled']
            ? $this->getAvailableEmbedderIdentifier($indexUid, (int)$settings['embedder_id'])
            : null;
        $threshold = (float)$settings['ranking_score_threshold'];

        return [
            'hybridSearch' => [
                'enabled' => $embedderIdentifier !== null,
                'semanticRatio' => (float)$settings['semantic_ratio'],
                'embedder' => $embedderIdentifier,
                'rankingScoreThreshold' => $threshold > 0 ? $threshold : null
            ]
        ];
    }

    /**
     * @param string $indexUid
     * @param int $embedderId
     * @return string|null
     * @throws LocalizedException
     */
    private function getAvailableEmbedderIdentifier(string $indexUid, int $embedderId): ?string
    {
        if ($embedderId <= 0 || !in_array($embedderId, array_map('intval', $this->linkResource->getEmbedderIdsByUid($indexUid)), true)) {
            return null;
        }

        try {
            $identifier = (string)$this->embedderRepository->getById($embedderId)->getIdentifier();
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $this->embedderAvailability->isAvailable($indexUid, $identifier) ? $identifier : null;
    }
}
