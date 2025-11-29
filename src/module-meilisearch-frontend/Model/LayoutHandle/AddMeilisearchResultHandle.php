<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\LayoutHandle;

use Walkwizus\MeilisearchFrontend\Api\LayoutHandleInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Catalog\Model\Category;

class AddMeilisearchResultHandle implements LayoutHandleInterface
{
    private const SUPPORTED_ACTIONS = [
        'catalog_category_view',
        'catalogsearch_result_index'
    ];

    /**
     * @param RequestInterface $request
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) { }

    /**
     * @param LayoutInterface $layout
     * @param string $fullActionName
     * @return bool
     */
    public function isApplicable(LayoutInterface $layout, string $fullActionName): bool
    {
        if (!in_array($fullActionName, self::SUPPORTED_ACTIONS, true)) {
            return false;
        }

        if ($fullActionName === 'catalogsearch_result_index') {
            return true;
        }

        $categoryId = $this->request->getParam('id', false);
        if (!$categoryId) {
            return false;
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (\Exception $e) {
            return false;
        }

        return $category->getDisplayMode() !== Category::DM_PAGE;
    }
}
