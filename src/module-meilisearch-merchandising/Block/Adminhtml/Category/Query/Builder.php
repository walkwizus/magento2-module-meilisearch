<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Block\Adminhtml\Category\Query;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;
use Magento\Framework\Locale\Format;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\UrlInterface;

class Builder extends Template
{
    const COMPONENT_NAME = 'meilisearch-merchandising-query-builder';

    /**
     * @var array|string[]
     */
    private array $ajaxUrls = [
        'loadRule' => 'meilisearch_merchandising/category/ajax_getrule',
        'saveRule' => 'meilisearch_merchandising/category/ajax_saverule',
        'deleteRule' => 'meilisearch_merchandising/category/ajax_deleterule',
        'preview' => 'meilisearch_merchandising/category/ajax_preview'
    ];

    /**
     * @param Context $context
     * @param QueryBuilderService $queryBuilderService
     * @param Format $format
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Template\Context $context,
        private readonly QueryBuilderService $queryBuilderService,
        private readonly Format $format,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getJsLayout()
    {
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['filters'] = $this->getFilters();
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['storeId'] = $this->getStoreId();
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['productMediaUrl'] = $this->getProductMediaUrl();
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['priceFormat'] = $this->format->getPriceFormat();

        foreach ($this->ajaxUrls as $key => $value) {
            $this->jsLayout['components'][self::COMPONENT_NAME]['config']['ajaxUrl'][$key] = $this->getUrl($value);
        }

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
     * @throws NoSuchEntityException
     */
    public function getFilters(): bool|string
    {
        return json_encode($this->queryBuilderService->convertAttributesToRules());
    }

    /**
     * @return string
     */
    public function getProductMediaUrl(): string
    {
        try {
            return $this->_storeManager->getStore($this->getStoreId())->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
        } catch (\Exception $e) {
            return '';
        }
    }
}
