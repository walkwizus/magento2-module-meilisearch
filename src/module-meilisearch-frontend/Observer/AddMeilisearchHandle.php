<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Search\Model\EngineResolver;
use Magento\Framework\View\LayoutInterface;

class AddMeilisearchHandle implements ObserverInterface
{
    /**
     * @var array|string[]
     */
    private array $fullActionName = [
        'catalog_category_view',
        'catalogsearch_result_index'
    ];

    /**
     * @param EngineResolver $engineResolver
     */
    public function __construct(
        private readonly EngineResolver $engineResolver
    ) { }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->engineResolver->getCurrentSearchEngine() !== 'meilisearch') {
            return;
        }

        $fullActionName = $observer->getData('full_action_name');

        /** @var LayoutInterface $layout */
        $layout = $observer->getData('layout');

        if (in_array($fullActionName, $this->fullActionName, true)) {
            $layout->getUpdate()->addHandle('remove_category_blocks');
            $layout->getUpdate()->addHandle('meilisearch_common');
            $layout->getUpdate()->addHandle('meilisearch_result');
        }
    }
}
