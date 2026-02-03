<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Controller\Adminhtml\Embedders;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Walkwizus_MeilisearchAi::embedders';

    /**
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Walkwizus_MeilisearchIndices::indices');
        $resultPage->getConfig()->getTitle()->prepend(__('AI Embedders Profiles'));

        return $resultPage;
    }
}
