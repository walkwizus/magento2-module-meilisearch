<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Block\Adminhtml\Category;

use Magento\Backend\Block\Template;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Tree extends Template
{
    const COMPONENT_NAME = 'meilisearch-merchandising-category-tree';

    /**
     * @param Template\Context $context
     * @param CategoryManagementInterface $categoryManagement
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Template\Context $context,
        private readonly CategoryManagementInterface $categoryManagement,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getJsLayout()
    {
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['categoryTree'] = $this->getCategoryTree();
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['loadRuleUrl'] = $this->getLoadRuleUrl();

        return parent::getJsLayout();
    }

    /**
     * @return mixed
     */
    public function getStoreId(): mixed
    {
        if ($this->_storeManager->isSingleStoreMode()) {
            return $this->_storeManager->getDefaultStoreView()->getId();
        }

        return $this->getRequest()->getParam('store', false);
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function getCategoryTree(): bool|string
    {
        $rootCategoryId = $this->_storeManager->getStore()->getRootCategoryId();

        try {
            $rootCategory = $this->categoryManagement->getTree($rootCategoryId);
            $jsTreeData = $this->convertToJsTreeFormat($rootCategory);
            return json_encode([$jsTreeData]);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $category
     * @return array
     */
    protected function convertToJsTreeFormat($category): array
    {
        $jsTreeFormat = [
            'text' => $category['name'],
            'id' => $category['id']
        ];

        if (!empty($category['children_data'])) {
            $jsTreeFormat['children'] = [];
            foreach ($category['children_data'] as $child) {
                $jsTreeFormat['children'][] = $this->convertToJsTreeFormat($child);
            }
        }

        return $jsTreeFormat;
    }

    /**
     * @return string
     */
    public function getLoadRuleUrl(): string
    {
        return $this->getUrl('meilisearch_merchandising/category/ajax_getrule');
    }
}
