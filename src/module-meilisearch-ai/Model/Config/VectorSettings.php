<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;

class VectorSettings
{
    const XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX = 'meilisearch_ai/indices_settings';
    const IS_VECTOR_ENABLED = 'is_vector_enabled';
    const EMBEDDER_ID = 'embedder_id';
    const SEMANTIC_RATIO = 'semantic_ratio';
    const RANKING_SCORE_THRESHOLD = 'ranking_score_threshold';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly WriterInterface $writer,
        private readonly ReinitableConfigInterface $reinitableConfig
    ) { }

    /**
     * @param string $indexUid
     * @return array
     */
    public function getVectorSettings(string $indexUid): array
    {
        return [
            'is_vector_enabled' => $this->getIsVectorEnabled($indexUid),
            'embedder_id' => $this->getEmbedderId($indexUid),
            'semantic_ratio' => $this->getSemanticRatio($indexUid),
            'ranking_score_threshold' => $this->getRankingScoreThreshold($indexUid)
        ];
    }

    /**
     * @param string $indexUid
     * @param bool $isEnabled
     * @param int $embedderId
     * @param float $semanticRatio
     * @param float|null $rankingScoreThreshold
     * @return array
     */
    public function setVectorSettings(
        string $indexUid,
        bool $isEnabled,
        int $embedderId,
        float $semanticRatio = 0.5,
        ?float $rankingScoreThreshold = null
    ): array {
        $this->setIsVectorEnabled($indexUid, $isEnabled);
        $this->setEmbedderId($indexUid, $embedderId);
        $this->setSemanticRatio($indexUid, $semanticRatio);
        $this->setRankingScoreThreshold($indexUid, $rankingScoreThreshold);

        $this->reinitableConfig->reinit();

        return $this->getVectorSettings($indexUid);
    }

    /**
     * @param string $indexUid
     * @return mixed
     */
    public function getIsVectorEnabled(string $indexUid): mixed
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::IS_VECTOR_ENABLED
        );
    }

    /**
     * @param string $indexUid
     * @return mixed
     */
    public function getEmbedderId(string $indexUid): mixed
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::EMBEDDER_ID
        );
    }

    /**
     * @param string $indexUid
     * @return mixed
     */
    public function getSemanticRatio(string $indexUid): mixed
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::SEMANTIC_RATIO
        );
    }

    /**
     * @param string $indexUid
     * @return mixed
     */
    public function getRankingScoreThreshold(string $indexUid): mixed
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::RANKING_SCORE_THRESHOLD
        );
    }

    /**
     * @param string $indexUid
     * @param bool $isEnabled
     * @return void
     */
    public function setIsVectorEnabled(string $indexUid, bool $isEnabled): void
    {
        $this->writer->save(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::IS_VECTOR_ENABLED,
            $isEnabled
        );
    }

    /**
     * @param string $indexUid
     * @param int $embedderId
     * @return void
     */
    public function setEmbedderId(string $indexUid, int $embedderId): void
    {
        $this->writer->save(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::EMBEDDER_ID,
            $embedderId
        );
    }

    /**
     * @param string $indexUid
     * @param float $semanticRatio
     * @return void
     */
    public function setSemanticRatio(string $indexUid, float $semanticRatio): void
    {
        $this->writer->save(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::SEMANTIC_RATIO,
            $semanticRatio
        );
    }

    /**
     * @param string $indexUid
     * @param float $rankingScoreThreshold
     * @return void
     */
    public function setRankingScoreThreshold(string $indexUid, float $rankingScoreThreshold): void
    {
        $this->writer->save(
            self::XML_PATH_MEILISEARCH_AI_INDICES_SETTINGS_PREFIX . '/' . $indexUid . '_' . self::RANKING_SCORE_THRESHOLD,
            $rankingScoreThreshold
        );
    }
}
