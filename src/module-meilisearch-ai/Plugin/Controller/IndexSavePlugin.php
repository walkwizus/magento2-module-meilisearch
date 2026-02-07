<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Plugin\Controller;

use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchIndices\Controller\Adminhtml\Indices\Save as SaveController;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory as EmbedderCollectionFactory;

class IndexSavePlugin
{
    /**
     * @param LinkResource $linkResource
     * @param RequestInterface $request
     * @param SettingsManager $settingsManager
     * @param EmbedderCollectionFactory $embedderCollectionFactory
     */
    public function __construct(
        private readonly LinkResource $linkResource,
        private readonly RequestInterface $request,
        private readonly SettingsManager $settingsManager,
        private readonly EmbedderCollectionFactory $embedderCollectionFactory
    ) { }

    /**
     * @param SaveController $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(SaveController $subject, $result)
    {
        $indexUid = (string)$this->request->getParam('id');
        $postData = $this->request->getPostValue();
        $embedderIds = $postData['embedder_ids'] ?? [];

        if ($indexUid) {
            try {
                $ids = is_array($embedderIds) ? $embedderIds : array_filter(explode(',', (string)$embedderIds));
                $this->linkResource->syncEmbedderLinks($indexUid, $ids);
                $meiliEmbeddersConfig = [];
                if (!empty($ids)) {
                    $collection = $this->embedderCollectionFactory->create();
                    $collection->addFieldToFilter('embedder_id', ['in' => $ids]);

                    /** @var EmbedderInterface $embedder */
                    foreach ($collection as $embedder) {
                        $meiliEmbeddersConfig[$embedder->getIdentifier()] = [
                            'source' => $embedder->getSource(),
                            'apiKey' => $embedder->getApiKey(),
                            'model' => $embedder->getModel(),
                            'documentTemplate' => $embedder->getDocumentTemplate()
                        ];
                    }
                }

                $this->settingsManager->updateEmbedders($indexUid, $meiliEmbeddersConfig);

            } catch (\Exception $e) {

            }
        }

        return $result;
    }
}
