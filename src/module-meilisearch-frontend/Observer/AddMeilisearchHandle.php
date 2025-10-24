<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Search\Model\EngineResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Walkwizus\MeilisearchBase\Model\ResourceModel\Engine;

class AddMeilisearchHandle implements ObserverInterface
{
    public const CATALOG_CATEGORY_VIEW_ACTION = 'catalog_category_view';

    public const CATALOGSEARCH_RESULT_INDEX_ACTION = 'catalogsearch_result_index';

    /**
     * @var array|string[]
     */
    private array $fullActionName = [
        self::CATALOG_CATEGORY_VIEW_ACTION,
        self::CATALOGSEARCH_RESULT_INDEX_ACTION
    ];

    /**
     * @param EngineResolver $engineResolver
     * @param RequestInterface $request
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        private readonly EngineResolver $engineResolver,
        private readonly RequestInterface $request,
        private readonly CategoryRepositoryInterface $categoryRepository
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

        $fullActionName = $observer->getData('full_action_name');

        /** @var LayoutInterface $layout */
        $layout = $observer->getData('layout');

        $layout->getUpdate()->addHandle('meilisearch_common');

        if (in_array($fullActionName, $this->fullActionName, true)) {
            $layout->getUpdate()->addHandle('remove_category_blocks');

            if ($fullActionName === self::CATALOGSEARCH_RESULT_INDEX_ACTION) {
                $layout->getUpdate()->addHandle('meilisearch_result');
                return;
            }

            $categoryId = $this->request->getParam('id');

            if ($categoryId) {
                try {
                    $currentCategory = $this->categoryRepository->get($categoryId);

                    if ($currentCategory->getDisplayMode() !== Category::DM_PAGE) {
                        $layout->getUpdate()->addHandle('meilisearch_result');
                    }
                } catch (\Exception $e) { }
            }
        }
    }
}
