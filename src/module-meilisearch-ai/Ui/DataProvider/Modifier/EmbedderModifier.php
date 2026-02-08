<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Ui\DataProvider\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchAi\Model\Config\VectorSettings;
use Magento\Framework\Exception\LocalizedException;

class EmbedderModifier implements ModifierInterface
{
    /**
     * @param LinkResource $linkResource
     * @param VectorSettings $vectorSettings
     */
    public function __construct(
        private readonly LinkResource $linkResource,
        private readonly VectorSettings $vectorSettings
    ) { }

    /**
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function modifyData(array $data): array
    {
        foreach ($data as $indexUid => $item) {
            $embedderIds = $this->linkResource->getEmbedderIdsByUid((string)$indexUid);
            $data[$indexUid]['embedder_ids'] = !empty($embedderIds) ? $embedderIds : [];

            $settings = $this->vectorSettings->getVectorSettings((string)$indexUid);

            $data[$indexUid]['is_vector_enabled'] = $settings['is_vector_enabled'];
            $data[$indexUid]['semantic_ratio'] = $settings['semantic_ratio'];
            $data[$indexUid]['embedder_id'] = $settings['embedder_id'];
        }

        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        return $meta;
    }
}
