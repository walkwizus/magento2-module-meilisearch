<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFactory;
use Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotDeleteException;

class DeleteRule extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Context $context,
        private JsonFactory $jsonFactory,
        private CategoryFactory $categoryFactory,
        private CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $categoryId = $this->getRequest()->getParam('categoryId', false);

        $json = $this->jsonFactory->create();

        try {
            $this->categoryRepository->deleteByCategoryId($categoryId);
        } catch (CouldNotDeleteException $couldNotDeleteException) {
            return $json->setData(['success'=> false, 'message' => __($couldNotDeleteException->getMessage())]);
        } catch (\Exception $e) {
            return $json->setData(['success'=> false, 'message' => __($e->getMessage())]);
        }

        return $json->setData(['success'=> true, 'message' => __('Category was deleted')]);
    }
}
