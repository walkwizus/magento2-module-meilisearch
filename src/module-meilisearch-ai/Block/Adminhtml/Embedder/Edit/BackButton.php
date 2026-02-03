<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Block\Adminhtml\Embedder\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\UrlInterface;

class BackButton implements ButtonProviderInterface
{
    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) { }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->urlBuilder->getUrl('*/*/index')),
            'class' => 'back',
            'sort_order' => 10
        ];
    }
}
