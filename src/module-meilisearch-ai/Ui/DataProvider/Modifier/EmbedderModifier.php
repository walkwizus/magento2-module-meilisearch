<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Ui\DataProvider\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;

class EmbedderModifier implements ModifierInterface
{
    /**
     * @param LinkResource $linkResource
     */
    public function __construct(
        private readonly LinkResource $linkResource
    ) { }

    /**
     * @param array $data
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function modifyData(array $data): array
    {
        foreach ($data as $indexUid => $item) {
            $embedderIds = $this->linkResource->getEmbedderIdsByUid((string)$indexUid);
            $data[$indexUid]['embedder_ids'] = !empty($embedderIds) ? $embedderIds : [];
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
