<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Plugin\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchAi\Model\EmbedderSynchronizer;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchIndices\Controller\Adminhtml\Indices\Save as SaveController;
use Walkwizus\MeilisearchAi\Model\Config\VectorSettings;

class IndexSavePlugin
{
    /**
     * @param LinkResource $linkResource
     * @param RequestInterface $request
     * @param EmbedderSynchronizer $embedderSynchronizer
     * @param VectorSettings $vectorSettings
     * @param MessageManager $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LinkResource $linkResource,
        private readonly RequestInterface $request,
        private readonly EmbedderSynchronizer $embedderSynchronizer,
        private readonly VectorSettings $vectorSettings,
        private readonly MessageManager $messageManager,
        private readonly LoggerInterface $logger
    ) { }

    /**
     * @param SaveController $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(SaveController $subject, $result): mixed
    {
        $indexUid = (string)$this->request->getParam('id');

        if ($indexUid === '') {
            return $result;
        }

        $postData = $this->request->getPostValue();
        $embedderIds = $postData['embedder_ids'] ?? [];
        $ids = is_array($embedderIds) ? $embedderIds : array_filter(explode(',', (string)$embedderIds));
        $threshold = (string)($postData['ranking_score_threshold'] ?? '');

        try {
            $this->linkResource->syncEmbedderLinks($indexUid, $ids);
            $this->vectorSettings->setVectorSettings(
                $indexUid,
                (bool)($postData['is_vector_enabled'] ?? false),
                (int)($postData['search_embedder_id'] ?? 0),
                (float)($postData['semantic_ratio'] ?? 0.5),
                $threshold !== '' ? (float)$threshold : 0.0
            );

            if ($this->embedderSynchronizer->sync($indexUid) !== null) {
                $this->messageManager->addNoticeMessage(
                    __('The embedders of index %1 were sent to Meilisearch. Embeddings are being generated in the background.', $indexUid)
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Meilisearch: unable to save the embedders of index ' . $indexUid, ['exception' => $e]);
            $this->messageManager->addErrorMessage(
                __('The embedders of index %1 could not be saved: %2', $indexUid, $e->getMessage())
            );
        }

        return $result;
    }
}
