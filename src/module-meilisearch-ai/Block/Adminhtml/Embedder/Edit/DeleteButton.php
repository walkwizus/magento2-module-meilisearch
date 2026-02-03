<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Block\Adminhtml\Embedder\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\UrlInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @param Context $context
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly Context $context,
        private readonly UrlInterface $urlBuilder
    ) {}

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        $embedderId = $this->context->getRequest()->getParam('embedder_id');

        if ($embedderId) {
            $data = [
                'label' => __('Delete Embedder'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . __(
                        'Are you sure you want to do this?'
                    ) . '\', \'' . $this->getDeleteUrl((int)$embedderId) . '\', {"data": {}})',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * @param int $embedderId
     * @return string
     */
    public function getDeleteUrl(int $embedderId): string
    {
        return $this->urlBuilder->getUrl('*/*/delete', ['embedder_id' => $embedderId]);
    }
}
