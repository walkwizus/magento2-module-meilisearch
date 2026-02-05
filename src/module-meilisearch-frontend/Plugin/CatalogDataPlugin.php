<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Plugin;

use Magento\Framework\Registry;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Session;
use Magento\Catalog\Helper\Data as CatalogHelper;

class CatalogDataPlugin
{
    /**
     * @param Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Session $catalogSession
     */
    public function __construct(
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly Session $catalogSession
    ) { }

    /**
     * @param CatalogHelper $subject
     * @return null
     */
    public function beforeGetBreadcrumbPath(CatalogHelper $subject): null
    {
        if ($this->registry->registry('current_category')) {
            return null;
        }

        $categoryId = $this->catalogSession->getLastVisitedCategoryId();

        if ($categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
                $this->registry->register('current_category', $category);
            } catch (\Exception $e) {

            }
        }
        return null;
    }
}
