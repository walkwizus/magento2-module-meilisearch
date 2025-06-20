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
use Magento\Framework\Exception\NoSuchEntityException;
use Walkwizus\MeilisearchMerchandising\Service\SaveProductPosition;

class SaveRule extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param SaveProductPosition $saveProductPosition
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SaveProductPosition $saveProductPosition
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $json = $this->jsonFactory->create();

        $storeId = $this->getRequest()->getParam('storeId');
        $categoryId = $this->getRequest()->getParam('categoryId', false);
        $rules = $this->getRequest()->getParam('rules', false);
        $documentPositions = $this->getRequest()->getParam('docPositions', false);

        if (!$rules) {
            return $json->setData(['success' => false, 'message' => __('Invalid rules')]);
        }

        $rules = json_decode($rules, true);

        unset($rules['valid']);

        try {
            $categoryRule = $this->categoryRepository->getByCategoryId($categoryId);
            $categoryRule->setCategoryId($categoryId);
            $categoryRule->setStoreId($storeId);
            $categoryRule->setQuery(json_encode($rules));
        } catch (NoSuchEntityException $e) {
            $categoryRule = $this->categoryFactory->create();
            $categoryRule->setStoreId($storeId);
            $categoryRule->setCategoryId($categoryId);
            $categoryRule->setQuery(json_encode($rules));
        } catch (\Exception $e) {
            return $json->setData(['success'=> false, 'message' => __($e->getMessage())]);
        }

        $this->categoryRepository->save($categoryRule);

        if ($documentPositions) {
            $documentPositions = json_decode($documentPositions, true);
            $this->saveProductPosition->execute($documentPositions, $categoryId, $storeId);
        }

        return $json->setData(['success'=> true, 'message' => __('Category was saved')]);
    }
}
