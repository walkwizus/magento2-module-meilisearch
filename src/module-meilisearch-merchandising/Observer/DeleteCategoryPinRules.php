<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Observer;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchMerchandising\Service\CategoryPinRuleSync;

class DeleteCategoryPinRules implements ObserverInterface
{
    /**
     * @param CategoryPinRuleSync $categoryPinRuleSync
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CategoryPinRuleSync $categoryPinRuleSync,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Category|null $category */
        $category = $observer->getEvent()->getData('category');

        if (!$category || !$category->getId()) {
            return;
        }

        try {
            $this->categoryPinRuleSync->deleteCategory((int) $category->getId());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
