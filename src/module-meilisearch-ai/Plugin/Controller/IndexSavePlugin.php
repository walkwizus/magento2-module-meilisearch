<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Plugin\Controller;

use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\Link as LinkResource;
use Walkwizus\MeilisearchIndices\Controller\Adminhtml\Indices\Save as SaveController;

class IndexSavePlugin
{
    /**
     * @param LinkResource $linkResource
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly LinkResource $linkResource,
        private readonly RequestInterface $request
    ) {}

    /**
     * @param SaveController $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(SaveController $subject, $result)
    {
        $indexUid = $this->request->getParam('id');
        $postData = $this->request->getPostValue();

        $embedderIds = $postData['embedder_ids'] ?? [];

        if ($indexUid) {
            try {
                $ids = is_array($embedderIds) ? $embedderIds : explode(',', (string)$embedderIds);
                $ids = array_filter($ids);
                $this->linkResource->syncEmbedderLinks((string)$indexUid, $ids);

            } catch (\Exception $e) {

            }
        }

        return $result;
    }
}
