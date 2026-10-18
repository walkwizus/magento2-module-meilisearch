<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductPreview;
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductArrangement;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Preview extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Walkwizus_MeilisearchMerchandising::merchandising_category';

    private const DEFAULT_PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 200;

    /**
     * @param Context $context
     * @param CategoryProductPreview $categoryProductPreview
     * @param CategoryProductArrangement $categoryProductArrangement
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        private readonly CategoryProductPreview $categoryProductPreview,
        private readonly CategoryProductArrangement $categoryProductArrangement,
        private readonly JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $json = $this->jsonFactory->create();
        $rules = json_decode((string) $this->getRequest()->getParam('rules', ''), true);
        $filters = json_decode((string) $this->getRequest()->getParam('filters', '[]'), true);
        $arrangement = json_decode((string) $this->getRequest()->getParam('arrangement', '[]'), true);
        $pageSize = (int) $this->getRequest()->getParam('pageSize', self::DEFAULT_PAGE_SIZE);
        $storeId = (int) $this->getRequest()->getParam('storeId');
        $categoryId = (int) $this->getRequest()->getParam('categoryId');
        $saved = null;

        try {
            if ($this->getRequest()->getParam('withSaved')) {
                $saved = $this->categoryProductArrangement->getSaved($storeId, $categoryId);
                $arrangement = json_decode((string) json_encode($saved), true);
            }

            $result = $this->categoryProductPreview->getPage(
                is_array($rules) ? $rules : null,
                $storeId,
                $categoryId,
                is_array($arrangement) ? $arrangement : [],
                max(1, (int) $this->getRequest()->getParam('page', 1)),
                min(self::MAX_PAGE_SIZE, max(1, $pageSize)),
                trim((string) $this->getRequest()->getParam('query', '')),
                is_array($filters) ? $filters : []
            );
        } catch (\Exception $e) {
            return $json->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        return $json->setData(['success' => true, 'saved' => $saved] + $result);
    }
}
