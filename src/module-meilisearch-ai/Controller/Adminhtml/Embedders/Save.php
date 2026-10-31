<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Controller\Adminhtml\Embedders;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchAi\Api\EmbedderRepositoryInterface;
use Walkwizus\MeilisearchAi\Model\EmbedderFactory;
use Walkwizus\MeilisearchAi\Model\EmbedderSynchronizer;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Psr\Log\LoggerInterface;
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
     * @param EmbedderSynchronizer $embedderSynchronizer
     * @param LinkResource $linkResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly EmbedderRepositoryInterface $embedderRepository,
        private readonly EmbedderFactory $embedderFactory,
        private readonly EmbedderSynchronizer $embedderSynchronizer,
        private readonly LinkResource $linkResource,
        private readonly LoggerInterface $logger
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

            if (($data[EmbedderInterface::API_KEY] ?? '') === '') {
                $data[EmbedderInterface::API_KEY] = $model->getApiKey();
            }

            $model->setData($data);

            try {
                $this->embedderRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the embedder profile.'));
                $this->dataPersistor->clear('meilisearch_ai_embedder');
                $this->syncLinkedIndexes((int)$model->getId());

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['embedder_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the embedder.'));
            }

            unset($data[EmbedderInterface::API_KEY]);
            $this->dataPersistor->set('meilisearch_ai_embedder', $data);

            return $resultRedirect->setPath('*/*/edit', ['embedder_id' => $id]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param int $embedderId
     * @return void
     * @throws LocalizedException
     */
    private function syncLinkedIndexes(int $embedderId): void
    {
        foreach ($this->linkResource->getIndexUidsByEmbedderId($embedderId) as $indexUid) {
            try {
                if ($this->embedderSynchronizer->sync((string)$indexUid) !== null) {
                    $this->messageManager->addNoticeMessage(
                        __('The embedders of index %1 were sent to Meilisearch. Embeddings are being generated in the background.', $indexUid)
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Meilisearch: unable to sync the embedders of index ' . $indexUid, ['exception' => $e]);
                $this->messageManager->addErrorMessage(
                    __('The embedders of index %1 could not be sent to Meilisearch: %2', $indexUid, $e->getMessage())
                );
            }
        }
    }
}
