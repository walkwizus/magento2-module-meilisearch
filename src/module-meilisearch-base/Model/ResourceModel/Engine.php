<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchBase\Model\ResourceModel;

use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\Catalog\Model\Product\Visibility;

class Engine implements EngineInterface
{
    public const SEARCH_ENGINE = 'meilisearch';

    /**
     * @param Visibility $catalogProductVisibility
     */
    public function __construct(
        private readonly Visibility $catalogProductVisibility
    ) { }

    /**
     * @return array
     */
    public function getAllowedVisibility(): array
    {
        return $this->catalogProductVisibility->getVisibleInSiteIds();
    }

    /**
     * @return bool
     */
    public function allowAdvancedIndex(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function processAttributeValue($attribute, $value)
    {
        return $value;
    }

    /**
     * @param $index
     * @param string $separator
     * @return array
     */
    public function prepareEntityIndex($index, $separator = ' '): array
    {
        return $index;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool
    {
        return true;
    }
}
