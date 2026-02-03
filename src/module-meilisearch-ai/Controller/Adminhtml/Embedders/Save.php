<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Controller\Adminhtml\Embedders;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Walkwizus\MeilisearchAi\Api\EmbedderRepositoryInterface;
use Walkwizus\MeilisearchAi\Model\EmbedderFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Walkwizus_MeilisearchAi::embedders';

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param EmbedderRepositoryInterface $embedderRepository
     * @param EmbedderFactory $embedderFactory
     */
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly EmbedderRepositoryInterface $embedderRepository,
        private readonly EmbedderFactory $embedderFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $id = $this->getRequest()->getParam('embedder_id');
            $model = $this->embedderFactory->create();

            if ($id) {
                try {
                    $model = $this->embedderRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This embedder no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $model->setData($data);

            try {
                $this->embedderRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the embedder profile.'));
                $this->dataPersistor->clear('meilisearch_ai_embedder');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['embedder_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the embedder.'));
            }

            $this->dataPersistor->set('meilisearch_ai_embedder', $data);

            return $resultRedirect->setPath('*/*/edit', ['embedder_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
