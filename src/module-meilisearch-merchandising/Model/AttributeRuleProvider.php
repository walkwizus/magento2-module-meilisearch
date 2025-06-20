<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model;

use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;

class AttributeRuleProvider
{
    /**
     * @param AttributeProvider $attributeProvider
     * @param AttributeCollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        private readonly AttributeProvider $attributeProvider,
        private readonly AttributeCollectionFactory $attributeCollectionFactory
    ) { }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        $filterableAttributes = $this->attributeProvider->getFilterableAttributes('catalog_product', 'category_merchandising');

        $attributeCollection = $this->attributeCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_code', ['in' => $filterableAttributes]);

        $attributes = [];
        /** @var ProductAttributeInterface $attribute */
        foreach ($attributeCollection as $attribute) {
            $attributes[] = [
                'code' => $attribute->getAttributeCode(),
                'type' => $attribute->getFrontendInput(),
                'label' => $attribute->getDefaultFrontendLabel()
            ];
        }

        return $attributes;
    }
}
