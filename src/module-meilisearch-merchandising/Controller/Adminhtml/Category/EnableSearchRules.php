<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Service\DynamicSearchRuleManager;
use Walkwizus\MeilisearchMerchandising\Service\CategoryPinRuleSync;

class EnableSearchRules extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Walkwizus_MeilisearchMerchandising::merchandising_category';

    /**
     * @param Context $context
     * @param DynamicSearchRuleManager $ruleManager
     * @param CategoryPinRuleSync $categoryPinRuleSync
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly DynamicSearchRuleManager $ruleManager,
        private readonly CategoryPinRuleSync $categoryPinRuleSync,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $this->ruleManager->enable();
            $count = $this->categoryPinRuleSync->syncAll();
            $this->messageManager->addSuccessMessage(
                __('Meilisearch search rules are enabled. %1 category rule(s) synchronized.', $count)
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Search rules could not be enabled: %1', $e->getMessage()));
        }

        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $store = $this->getRequest()->getParam('store');

        return $redirect->setPath('*/*/index', $store ? ['store' => $store] : []);
    }
}
