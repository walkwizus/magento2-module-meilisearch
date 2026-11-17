<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductArrangement;

class ResetPositions extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Walkwizus_MeilisearchMerchandising::merchandising_category';

    /**
     * @param Context $context
     * @param CategoryProductArrangement $categoryProductArrangement
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
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
        $storeId = (int) $this->getRequest()->getParam('storeId');
        $categoryId = (int) $this->getRequest()->getParam('categoryId');

        if (!$categoryId) {
            return $json->setData(['success' => false, 'message' => __('Invalid category')]);
        }

        try {
            $this->categoryProductArrangement->reset($storeId, $categoryId);
        } catch (\Exception $e) {
            return $json->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        return $json->setData(['success' => true, 'message' => __('The category now uses the native product order')]);
    }
}
