<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Plugin\Controller;

use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchIndices\Controller\Adminhtml\Indices\Save as SaveController;
use Walkwizus\MeilisearchBase\Service\SettingsManager;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory as EmbedderCollectionFactory;
use Walkwizus\MeilisearchAi\Model\Config\VectorSettings;

class IndexSavePlugin
{
    /**
     * @param LinkResource $linkResource
     * @param RequestInterface $request
     * @param SettingsManager $settingsManager
     * @param EmbedderCollectionFactory $embedderCollectionFactory
     * @param VectorSettings $vectorSettings
     */
    public function __construct(
        private readonly LinkResource $linkResource,
        private readonly RequestInterface $request,
        private readonly SettingsManager $settingsManager,
        private readonly EmbedderCollectionFactory $embedderCollectionFactory,
        private readonly VectorSettings $vectorSettings
    ) { }

    /**
     * @param SaveController $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(SaveController $subject, $result): mixed
    {
        $indexUid = (string)$this->request->getParam('id');
        $postData = $this->request->getPostValue();
        $embedderIds = $postData['embedder_ids'] ?? [];

        if ($indexUid) {
            try {
                $ids = is_array($embedderIds) ? $embedderIds : array_filter(explode(',', (string)$embedderIds));
                $this->linkResource->syncEmbedderLinks($indexUid, $ids);
                $meiliEmbeddersConfig = [];
                $newIdentifiers = [];

                if (!empty($ids)) {
                    $collection = $this->embedderCollectionFactory->create();
                    $collection->addFieldToFilter('embedder_id', ['in' => $ids]);

                    /** @var EmbedderInterface $embedder */
                    foreach ($collection as $embedder) {
                        $identifier = $embedder->getIdentifier();
                        $newIdentifiers[] = $identifier;
                        $meiliEmbeddersConfig[$identifier] = [
                            'source' => $embedder->getSource(),
                            'apiKey' => $embedder->getApiKey(),
                            'model' => $embedder->getModel(),
                            'documentTemplate' => $embedder->getDocumentTemplate()
                        ];
                    }
                }

                $currentMeiliSettings = $this->settingsManager->getEmbedders($indexUid);
                if (is_array($currentMeiliSettings)) {
                    foreach (array_keys($currentMeiliSettings) as $currentIdentifier) {
                        if (!in_array($currentIdentifier, $newIdentifiers)) {
                            $meiliEmbeddersConfig[$currentIdentifier] = null;
                        }
                    }
                }

                $this->settingsManager->updateEmbedders($indexUid, $meiliEmbeddersConfig);
                $this->vectorSettings->setVectorSettings(
                    $indexUid,
                    (bool)($postData['is_vector_enabled'] ?? false),
                    (int)($postData['search_embedder_id'] ?? 0),
                    (float)($postData['semantic_ratio'] ?? 0.5)
                );

            } catch (\Exception $e) {

            }
        }

        return $result;
    }
}
