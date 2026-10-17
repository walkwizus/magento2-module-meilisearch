<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductPreview;
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
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        private readonly CategoryProductPreview $categoryProductPreview,
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

        if (!is_array($rules)) {
            return $json->setData(['success' => false, 'message' => __('Invalid rules')]);
        }

        $moves = json_decode((string) $this->getRequest()->getParam('moves', '[]'), true);
        $pageSize = (int) $this->getRequest()->getParam('pageSize', self::DEFAULT_PAGE_SIZE);

        try {
            $result = $this->categoryProductPreview->getPage(
                $rules,
                (int) $this->getRequest()->getParam('storeId'),
                (int) $this->getRequest()->getParam('categoryId'),
                is_array($moves) ? $moves : [],
                max(1, (int) $this->getRequest()->getParam('page', 1)),
                min(self::MAX_PAGE_SIZE, max(1, $pageSize)),
                trim((string) $this->getRequest()->getParam('query', ''))
            );
        } catch (\Exception $e) {
            return $json->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        return $json->setData(['success' => true] + $result);
    }
}
