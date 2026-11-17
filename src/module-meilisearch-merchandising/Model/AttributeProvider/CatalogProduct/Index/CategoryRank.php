<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\AttributeProvider\CatalogProduct\Index;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Walkwizus\MeilisearchBase\Api\AttributeProviderInterface;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\SortFields;

class CategoryRank implements AttributeProviderInterface
{
    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param SortFields $sortFields
     */
    public function __construct(
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly SortFields $sortFields
    ) {
    }

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
        $rankAttributes = [];

        foreach ($this->categoryCollectionFactory->create()->getAllIds() as $categoryId) {
            $rankAttributes[] = $this->sortFields->getRankField((int) $categoryId);
        }

        return $rankAttributes;
    }
}
