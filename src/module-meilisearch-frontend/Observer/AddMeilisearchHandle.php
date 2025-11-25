<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Search\Model\EngineResolver;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;
use Walkwizus\MeilisearchFrontend\Api\LayoutHandleInterface;

class AddMeilisearchHandle implements ObserverInterface
{
    /**
     * @param EngineResolver $engineResolver
     * @param array $handles
     */
    public function __construct(
        private readonly EngineResolver $engineResolver,
        private readonly array $handles = []
    ) { }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->engineResolver->getCurrentSearchEngine() !== Engine::SEARCH_ENGINE) {
            return;
        }

        /** @var LayoutInterface $layout */
        $layout = $observer->getData('layout');
        $fullActionName = $observer->getData('full_action_name');

        /** @var LayoutHandleInterface $handle */
        foreach ($this->handles as $handleName => $handle) {
            if ($handle->isApplicable($layout, $fullActionName)) {
                $layout->getUpdate()->addHandle($handleName);
            }
        }
    }
}
