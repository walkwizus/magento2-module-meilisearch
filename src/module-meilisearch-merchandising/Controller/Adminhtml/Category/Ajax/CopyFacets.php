<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Walkwizus\MeilisearchMerchandising\Service\CategoryFacetService;

class CopyFacets extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Walkwizus_MeilisearchMerchandising::merchandising_category';

    /**
     * @param Context $context
     * @param CategoryFacetService $categoryFacetService
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        private readonly CategoryFacetService $categoryFacetService,
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
            $result = $this->categoryFacetService->copy(
                $storeId,
                $categoryId,
                (array) $this->getRequest()->getParam('targetStoreIds', []),
                (array) $this->getRequest()->getParam('targetCategoryIds', []),
                (bool) $this->getRequest()->getParam('includeChildren'),
                (bool) $this->getRequest()->getParam('overwrite')
            );

            return $json->setData([
                'success' => true,
                'message' => __('Configuration copied to %1 categories, %2 skipped.', $result['copied'], $result['skipped'])
            ] + $result);
        } catch (\Exception $e) {
            return $json->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
