<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Block\Adminhtml\Index;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Walkwizus\MeilisearchAi\Model\EmbeddingStatus as EmbeddingStatusModel;

class EmbeddingStatus extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Walkwizus_MeilisearchAi::index/embedding-status.phtml';

    /**
     * @param Context $context
     * @param EmbeddingStatusModel $embeddingStatus
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly EmbeddingStatusModel $embeddingStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getStatus(): array
    {
        return $this->embeddingStatus->get((string) $this->getRequest()->getParam('id'));
    }

    /**
     * @param array $status
     * @return string
     */
    public function getLabel(array $status): string
    {
        return (string) $this->embeddingStatus->getLabel($status);
    }

    /**
     * @param array $status
     * @return string
     */
    public function getMessageType(array $status): string
    {
        return match ($status['state']) {
            EmbeddingStatusModel::STATE_READY => 'success',
            EmbeddingStatusModel::STATE_FAILED, EmbeddingStatusModel::STATE_UNKNOWN => 'error',
            EmbeddingStatusModel::STATE_PROCESSING, EmbeddingStatusModel::STATE_INCOMPLETE => 'warning',
            default => 'notice'
        };
    }
}
