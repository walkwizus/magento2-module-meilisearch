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
use Walkwizus\MeilisearchMerchandising\Service\CategoryProductArrangement;

class SaveRule extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Walkwizus_MeilisearchMerchandising::merchandising_category';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryProductArrangement $categoryProductArrangement
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryProductArrangement $categoryProductArrangement
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
        $rules = json_decode((string) $this->getRequest()->getParam('rules', ''), true);
        $arrangement = json_decode((string) $this->getRequest()->getParam('arrangement', ''), true);
        $hasRules = is_array($rules) && !empty($rules['rules']);

        if (!$categoryId) {
            return $json->setData(['success' => false, 'message' => __('Invalid category')]);
        }

        try {
            if ($hasRules) {
                unset($rules['valid']);
                $this->saveRule($storeId, $categoryId, $rules);
            } elseif (!$this->hasRule($categoryId) && !is_array($arrangement)) {
                return $json->setData(['success' => false, 'message' => __('Nothing to save')]);
            } elseif ($this->hasRule($categoryId)) {
                return $json->setData(['success' => false, 'message' => __('Invalid rules')]);
            }

            if (is_array($arrangement)) {
                $this->categoryProductArrangement->save($hasRules ? $rules : null, $storeId, $categoryId, $arrangement);
            }
        } catch (\Exception $e) {
            return $json->setData(['success' => false, 'message' => __($e->getMessage())]);
        }

        return $json->setData(['success' => true, 'message' => __('Category merchandising was saved')]);
    }

    /**
     * @param int $storeId
     * @param int $categoryId
     * @param array $rules
     * @return void
     * @throws \Exception
     */
    private function saveRule(int $storeId, int $categoryId, array $rules): void
    {
        try {
            $categoryRule = $this->categoryRepository->getByCategoryId($categoryId);
        } catch (NoSuchEntityException $e) {
            $categoryRule = $this->categoryFactory->create();
        }

        $categoryRule->setCategoryId($categoryId);
        $categoryRule->setStoreId($storeId);
        $categoryRule->setQuery(json_encode($rules));
        $this->categoryRepository->save($categoryRule);
    }

    /**
     * @param int $categoryId
     * @return bool
     */
    private function hasRule(int $categoryId): bool
    {
        try {
            $this->categoryRepository->getByCategoryId($categoryId);

            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
}
