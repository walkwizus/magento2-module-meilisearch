<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Controller\Adminhtml\Embedders;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchAi\Api\EmbedderRepositoryInterface;

class Delete extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param EmbedderRepositoryInterface $embedderRepository
     */
    public function __construct(
        Context $context,
        private readonly EmbedderRepositoryInterface $embedderRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = $this->getRequest()->getParam('embedder_id');
        $this->embedderRepository->deleteById($id);

        return $resultRedirect->setPath('*/*/');
    }
}
