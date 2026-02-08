<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchAi\Model\Config\VectorSettings;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchAi\Api\EmbedderRepositoryInterface;

class HybridConfigProvider implements ConfigProviderInterface
{
    /**
     * @param VectorSettings $vectorSettings
     * @param SearchIndexNameResolver $indexNameResolver
     * @param StoreManagerInterface $storeManager
     * @param EmbedderRepositoryInterface $embedderRepository
     */
    public function __construct(
        private readonly VectorSettings $vectorSettings,
        private readonly SearchIndexNameResolver $indexNameResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly EmbedderRepositoryInterface $embedderRepository
    ) { }

    /**
     * @return array[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $indexUid = $this->indexNameResolver->getIndexName($storeId, 'catalog_product');

        $settings = $this->vectorSettings->getVectorSettings($indexUid);

        $embedderIdentifier = null;
        $embedderId = $settings['embedder_id'] ?? null;

        if ($embedderId) {
            try {
                $embedder = $this->embedderRepository->getById((int)$embedderId);
                $embedderIdentifier = $embedder->getIdentifier();
            } catch (\Exception $e) {

            }
        }

        return [
            'hybridSearch' => [
                'enabled' => (bool)$settings['is_vector_enabled'],
                'semanticRatio' => (float)$settings['semantic_ratio'],
                'embedder' => $embedderIdentifier,
                'rankingScoreThreshold' => (float)$settings['ranking_score_threshold']
            ]
        ];
    }
}
