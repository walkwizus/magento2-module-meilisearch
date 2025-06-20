<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class GetRule extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param CategoryRepositoryInterface $categoryRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        private CategoryRepositoryInterface $categoryRepository,
        private JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $categoryId = $this->getRequest()->getParam('category_id');
        $json = $this->jsonFactory->create();

        try {
            $rule = $this->categoryRepository->getByCategoryId($categoryId);
            return $json->setData($rule->toArray());
        } catch (\Exception $e) {
            return $json->setData([]);
        }
    }
}
