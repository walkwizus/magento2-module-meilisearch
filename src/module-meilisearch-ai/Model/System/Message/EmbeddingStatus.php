<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\System\Message;

use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Walkwizus\MeilisearchAi\Model\EmbeddingStatus as EmbeddingStatusModel;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;

class EmbeddingStatus implements MessageInterface
{
    private const DISPLAYED_STATES = [
        EmbeddingStatusModel::STATE_PROCESSING,
        EmbeddingStatusModel::STATE_FAILED,
        EmbeddingStatusModel::STATE_INCOMPLETE
    ];

    /**
     * @var array|null
     */
    private ?array $statuses = null;

    /**
     * @param EmbeddingStatusModel $embeddingStatus
     * @param LinkResource $linkResource
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly EmbeddingStatusModel $embeddingStatus,
        private readonly LinkResource $linkResource,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getIdentity(): string
    {
        return 'meilisearch_ai_embedding_status_' . hash('sha256', (string) json_encode(array_map(
            static fn (array $status): string => $status['state'],
            $this->getStatuses()
        )));
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isDisplayed(): bool
    {
        return $this->getStatuses() !== [];
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getText(): string
    {
        $lines = [];

        foreach ($this->getStatuses() as $indexUid => $status) {
            $lines[] = sprintf(
                '<a href="%s">%s</a>: %s',
                $this->escaper->escapeUrl($this->urlBuilder->getUrl('meilisearch_indices/indices/edit', ['id' => $indexUid])),
                $this->escaper->escapeHtml($indexUid),
                $this->escaper->escapeHtml((string) $this->embeddingStatus->getLabel($status))
            );
        }

        return $this->escaper->escapeHtml(__('Meilisearch embeddings:')) . ' ' . implode(' | ', $lines);
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getSeverity(): int
    {
        foreach ($this->getStatuses() as $status) {
            if ($status['state'] === EmbeddingStatusModel::STATE_FAILED) {
                return self::SEVERITY_MAJOR;
            }
        }

        return self::SEVERITY_NOTICE;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getStatuses(): array
    {
        if ($this->statuses !== null) {
            return $this->statuses;
        }

        $this->statuses = [];

        foreach ($this->linkResource->getLinkedIndexUids() as $indexUid) {
            $status = $this->embeddingStatus->get((string) $indexUid);

            if (in_array($status['state'], self::DISPLAYED_STATES, true)) {
                $this->statuses[(string) $indexUid] = $status;
            }
        }

        return $this->statuses;
    }
}
