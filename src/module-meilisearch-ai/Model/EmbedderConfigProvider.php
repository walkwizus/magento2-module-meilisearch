<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory as EmbedderCollectionFactory;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as EmbedderLinkResource;
use Walkwizus\MeilisearchBase\Api\EmbedderConfigProviderInterface;

class EmbedderConfigProvider implements EmbedderConfigProviderInterface
{
    /**
     * @param EmbedderLinkResource $embedderLinkResource
     * @param EmbedderCollectionFactory $embedderCollectionFactory
     */
    public function __construct(
        private readonly EmbedderLinkResource $embedderLinkResource,
        private readonly EmbedderCollectionFactory $embedderCollectionFactory
    ) { }

    /**
     * @param string $indexUid
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEmbedders(string $indexUid): array
    {
        $embedderIds = $this->embedderLinkResource->getEmbedderIdsByUid($indexUid);

        if (empty($embedderIds)) {
            return [];
        }

        $embedders = $this->embedderCollectionFactory
            ->create()
            ->addFieldToFilter('embedder_id', ['in' => $embedderIds]);

        $embeddersConfig = [];

        /** @var EmbedderInterface $embedder */
        foreach ($embedders as $embedder) {
            $embeddersConfig[$embedder->getIdentifier()] = [
                'source' => $embedder->getSource(),
                'model' => $embedder->getModel(),
                'apiKey' => $embedder->getApiKey(),
                'documentTemplate' => $embedder->getDocumentTemplate(),
            ];
        }

        return $embeddersConfig;
    }
}
