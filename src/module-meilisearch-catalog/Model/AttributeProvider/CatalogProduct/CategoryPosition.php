<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeProvider\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class CategoryPosition implements AttributeProviderInterface
{
    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        private readonly CategoryCollectionFactory $categoryCollectionFactory
    ) { }

    /**
     * @return array
     */
    public function getFilterableAttributes(): array
    {
        return [];
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
        $positionAttributes = [];

        $categories = $this->categoryCollectionFactory
            ->create()
            ->addFieldToSelect('entity_id');

        foreach ($categories as $category) {
            $positionAttributes[] = 'position_category_' . $category->getId();
        }

        return $positionAttributes;
    }
}
