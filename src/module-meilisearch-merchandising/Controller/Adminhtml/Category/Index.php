<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Walkwizus_MeilisearchMerchandising::merchandising_category';

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Walkwizus_MeilisearchMerchandising::merchandising_category'
        )->_addBreadcrumb(
            __('Category Merchandising'),
            __('Category Merchandising')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Category Merchandising'));
        $this->_view->renderLayout();
    }
}
