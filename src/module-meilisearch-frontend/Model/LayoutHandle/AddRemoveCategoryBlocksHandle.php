<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\LayoutHandle;

use Walkwizus\MeilisearchFrontend\Api\LayoutHandleInterface;
use Magento\Framework\View\LayoutInterface;

class AddRemoveCategoryBlocksHandle implements LayoutHandleInterface
{
    /**
     * @param LayoutInterface $layout
     * @param string $fullActionName
     * @return bool
     */
    public function isApplicable(LayoutInterface $layout, string $fullActionName): bool
    {
        if (in_array($fullActionName, ['catalog_category_view', 'catalogsearch_result_index'])) {
            return true;
        }

        return false;
    }
}
