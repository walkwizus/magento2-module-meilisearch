<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributeProvider\CatalogProduct\CategoryMerchandising;

use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Eav implements AttributeProviderInterface
{
    /**
     * @param AttributeCollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        private readonly AttributeCollectionFactory $attributeCollectionFactory
    ) { }

    /**
     * @return array
     */
    public function getFilterableAttributes(): array
    {
        $filterableAttributes = [];

        $attributes = $this->attributeCollectionFactory
            ->create()
            ->addIsFilterableFilter()
            ->addFieldToFilter('frontend_input', ['in' => ['select', 'multiselect', 'boolean', 'price']]);

        /** @var ProductAttributeInterface $attribute */
        foreach ($attributes as $attribute) {
            $filterableAttributes[] = $attribute->getAttributeCode();
        }

        return $filterableAttributes;
    }

    /**
     * @return array
     */
    public function getSearchableAttributes(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getSortableAttributes(): array
    {
        return [];
    }
}
