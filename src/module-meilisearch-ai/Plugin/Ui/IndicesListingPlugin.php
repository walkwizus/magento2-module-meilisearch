<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Plugin\Ui;

use Magento\Framework\Exception\LocalizedException;
use Walkwizus\MeilisearchAi\Model\EmbeddingStatus;
use Walkwizus\MeilisearchIndices\Ui\DataProvider\IndicesListing;

class IndicesListingPlugin
{
    /**
     * @param EmbeddingStatus $embeddingStatus
     */
    public function __construct(
        private readonly EmbeddingStatus $embeddingStatus
    ) {
    }

    /**
     * @param IndicesListing $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     */
    public function afterGetData(IndicesListing $subject, array $result): array
    {
        foreach ($result['items'] ?? [] as $key => $item) {
            $status = $this->embeddingStatus->get((string) $item['id']);
            $result['items'][$key]['embeddings'] = $status['state'] === EmbeddingStatus::STATE_NONE
                ? ''
                : (string) $this->embeddingStatus->getLabel($status);
        }

        return $result;
    }
}
