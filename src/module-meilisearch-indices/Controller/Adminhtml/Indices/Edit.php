<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Controller\Adminhtml\Indices;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;


class Edit extends Action implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Walkwizus_MeilisearchIndices::indices');
        $page->getConfig()->getTitle()->prepend(__('Edit Index Settings'));

        return $page;
    }
}
