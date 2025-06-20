<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Block\Adminhtml\Facet;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchMerchandising\Model\Config\Source\Facet\SortOptions;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class Configuration extends Template
{
    const COMPONENT_NAME = 'meilisearch-merchandising-facet';

    /**
     * @param Context $context
     * @param AttributeProvider $attributeProvider
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param SortOptions $sortOptions
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Template\Context $context,
        private readonly AttributeProvider $attributeProvider,
        private readonly AttributeCollectionFactory $attributeCollectionFactory,
        private readonly SortOptions $sortOptions,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $filterableAttributes = $this->getFormattedAttributes();
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['attributes'] = $filterableAttributes;
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['sortOptions'] = $this->sortOptions->toOptionArray();
        $this->jsLayout['components'][self::COMPONENT_NAME]['config']['saveUrl'] = $this->getUrl('meilisearch_merchandising/facet/save');

        return parent::getJsLayout();
    }

    /**
     * @return array
     */
    private function getFormattedAttributes(): array
    {
        $attributeCodes = $this->attributeProvider->getFilterableAttributes('catalog_product', 'merchandising');

        $attributeCollection = $this->attributeCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_code', ['in' => $attributeCodes]);

        $formattedAttributes = [];
        foreach ($attributeCollection as $attribute) {
            $formattedAttributes[] = $this->formatAttribute($attribute);
        }

        usort($formattedAttributes, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $formattedAttributes;
    }

    /**
     * @param ProductAttributeInterface $attribute
     * @return array
     */
    private function formatAttribute(ProductAttributeInterface $attribute): array
    {
        return [
            'code' => $attribute->getAttributeCode(),
            'label' => $attribute->getFrontendLabel(),
            'type' => $attribute->getFrontendInput(),
            'position' => $attribute->getPosition(),
            'operator' => $attribute->getMeilisearchOperator(),
            'show_more' => $attribute->getMeilisearchShowMore(),
            'show_more_limit' => $attribute->getMeilisearchShowMoreLimit(),
            'searchable' => $attribute->getMeilisearchSearchable(),
            'sort_values_by' => $attribute->getMeilisearchSortValuesBy()
        ];
    }
}
