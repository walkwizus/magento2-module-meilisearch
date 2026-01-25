<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeProvider\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class Eav implements AttributeProviderInterface
{
    /**
     * @var array|string[]
     */
    private array $additionalFilterableAttributes = [
        'id',
        'category_ids',
        'visibility',
        'type_id',
        'stock',
        'sku'
    ];

    /**
     * @var array
     */
    private array $additionalSearchableAttributes = [];

    /**
     * @var array
     */
    private array $additionalSortableAttributes = [];

    /**
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param array $additionalFilterableAttributes
     * @param array $additionalSearchableAttributes
     * @param array $additionalSortableAttributes
     */
    public function __construct(
        private readonly AttributeCollectionFactory $attributeCollectionFactory,
        array $additionalFilterableAttributes = [],
        array $additionalSearchableAttributes = [],
        array $additionalSortableAttributes = []
    ) {
        $this->additionalFilterableAttributes = array_unique(
            array_merge($additionalFilterableAttributes, $this->additionalFilterableAttributes)
        );
        $this->additionalSearchableAttributes = array_unique(
            array_merge($additionalSearchableAttributes, $this->additionalSearchableAttributes)
        );
        $this->additionalSortableAttributes = array_unique(
            array_merge($additionalSortableAttributes, $this->additionalSortableAttributes)
        );
    }

    /**
     * @return array
     */
    public function getFilterableAttributes(): array
    {
        $filterableAttributes = [];

        $attributes = $this->attributeCollectionFactory
            ->create()
            ->addIsFilterableFilter()
            ->addFieldToFilter('frontend_input', ['in' => ['select', 'multiselect', 'boolean']]);

        foreach ($attributes as $attribute) {
            $filterableAttributes[] = $attribute->getAttributeCode();
        }

        return array_merge($this->additionalFilterableAttributes, $filterableAttributes);
    }

    /**
     * @return array
     */
    public function getSearchableAttributes(): array
    {
        $searchableAttributes = [];

        $attributes = $this->attributeCollectionFactory->create()
            ->addIsSearchableFilter()
            ->addFieldToSelect('backend_type')
            ->addFieldToSelect('attribute_code');

        $attributes->getSelect()->order('search_weight DESC');

        foreach ($attributes as $attribute) {
            $searchableAttributes[] = $attribute->getAttributeCode();
        }

        return array_merge($this->additionalSearchableAttributes, $searchableAttributes);
    }

    /**
     * @return array
     */
    public function getSortableAttributes(): array
    {
        $sortableAttribute = [];

        $attributes = $this->attributeCollectionFactory->create()
            ->addFieldToSelect('attribute_code')
            ->addFieldToFilter('additional_table.used_for_sort_by', ['eq' => 1]);

        foreach ($attributes as $attribute) {
            $sortableAttribute[] = $attribute->getAttributeCode();
        }

        return array_merge($this->additionalSortableAttributes, $sortableAttribute);
    }
}
