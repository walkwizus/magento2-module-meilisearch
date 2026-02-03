<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Controller\Adminhtml\Embedders;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Walkwizus_MeilisearchAi::embedders';

    /**
     * @return Page
     */
    public function execute(): Page
    {
        $id = $this->getRequest()->getParam('embedder_id');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Walkwizus_MeilisearchAi::embedders');

        $title = $id ? __('Edit Embedder Profile') : __('New Embedder Profile');

        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
