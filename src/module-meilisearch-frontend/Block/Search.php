<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Block;

use Magento\Framework\View\Element\Template;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;

class Search extends Template
{
    /**
     * @param Template\Context $context
     * @param CatalogSearchHelper $catalogSearchHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private readonly CatalogSearchHelper $catalogSearchHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getSearchQueryText()
    {
        return __("Search results for: '%1'", $this->catalogSearchHelper->getEscapedQueryText());
    }

    /**
     * @return Search
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _prepareLayout()
    {
        if ($this->_request->getFullActionName() != 'catalogsearch_result_index') {
            return parent::_prepareLayout();
        }

        $title = $this->getSearchQueryText();
        $this->pageConfig->getTitle()->set($title);
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'search',
                ['label' => $title, 'title' => $title]
            );
        }

        return parent::_prepareLayout();
    }
}
