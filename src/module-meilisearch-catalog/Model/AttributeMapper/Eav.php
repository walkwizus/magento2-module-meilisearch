<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Api\Data\AttributeOptionInterface;

class Eav implements AttributeMapperInterface
{
    /**
     * @var array|string[]
     */
    private array $defaultExcludedAttributes = [
        'price',
        'media_gallery',
        'tier_price',
        'quantity_and_stock_status',
        'media_gallery',
        'giftcard_amounts',
    ];

    /**
     * @var array|string[]
     */
    private array $attributesExcludedFromMerge = [
        'status',
        'visibility',
        'tax_class_id',
    ];

    /**
     * @var array|string[]
     */
    private array $filterableAttributeTypes = [
        'boolean',
        'multiselect',
        'select',
    ];

    /**
     * @var array|string[]
     */
    private array $attributeParentProduct = [
        'name',
        'url_key',
    ];

    /**
     * @var AttributeOptionInterface[]
     */
    private array $attributeOptionsCache;

    /**
     * @var array|string[]
     */
    private array $excludedAttributes;

    /**
     * @param DataProvider $dataProvider
     * @param array $excludedAttributes
     */
    public function __construct(
        private readonly DataProvider $dataProvider,
        array $excludedAttributes = [],
    ) {
        $this->excludedAttributes = array_merge($this->defaultExcludedAttributes, $excludedAttributes);
    }

    /**
     * @param array $documentData
     * @param $storeId
     * @return array
     */
    public function map(array $documentData, $storeId): array
    {
        $documents = [];

        foreach ($documentData as $productId => $indexData) {
            $productIndexData = $this->convertToProductData($productId, $indexData, $storeId);
            foreach ($productIndexData as $attributeCode => $value) {
                $documents[$productId]['id'] = $productId;
                if (str_contains($attributeCode, '_value')) {
                    $documents[$productId][$attributeCode] = $value;
                    continue;
                }
                $documents[$productId][$attributeCode] = $value;
            }
        }

        return $documents;
    }

    /**
     * @param int $productId
     * @param array $indexData
     * @param $storeId
     * @return array
     */
    private function convertToProductData(int $productId, array $indexData, $storeId): array
    {
        $productAttributes = [];
        $searchableAttributes = $this->dataProvider->getSearchableAttributes();

        foreach ($indexData as $attributeId => $attributeValues) {
            if (isset($searchableAttributes[$attributeId])) {
                $attribute = $searchableAttributes[$attributeId];
                if (in_array($attribute->getAttributeCode(), $this->excludedAttributes, true)) {
                    continue;
                }
                if (!is_array($attributeValues)) {
                    $attributeValues = [$productId => $attributeValues];
                }
                $attributeValues = $this->prepareAttributeValues($productId, $attribute, $attributeValues);
                $productAttributes += $this->convertAttribute($attribute, $attributeValues, $storeId);
            }
        }

        return $productAttributes;
    }

    /**
     * @param int $productId
     * @param Attribute $attribute
     * @param array $attributeValues
     * @return array|int[]
     */
    private function prepareAttributeValues(int $productId, Attribute $attribute, array $attributeValues): array
    {
        if (in_array($attribute->getAttributeCode(), $this->attributesExcludedFromMerge, true)) {
            $attributeValues = [
                $productId => $attributeValues[$productId] ?? '',
            ];
        }

        if ($attribute->getFrontendInput() === 'multiselect') {
            $attributeValues = $this->prepareMultiselectValues($attributeValues);
        }

        if (in_array($attribute->getFrontendInput(), $this->filterableAttributeTypes)) {
            $attributeValues = array_map(
                function (string $valueId) {
                    return (int)$valueId;
                },
                $attributeValues
            );
        }

        if (in_array($attribute->getAttributeCode(), $this->attributeParentProduct) && count($attributeValues) > 1) {
            $attributeValues = [$attributeValues[$productId]];
        }

        return $attributeValues;
    }

    /**
     * @param Attribute $attribute
     * @param array $attributeValues
     * @param $storeId
     * @return array
     */
    private function convertAttribute(Attribute $attribute, array $attributeValues, $storeId): array
    {
        $productAttributes = [];
        $retrievedValue = $this->retrieveFieldValue($attributeValues);
        if ($retrievedValue !== null) {
            $productAttributes[$attribute->getAttributeCode()] = $retrievedValue;
            if ($this->isAttributeLabelsShouldBeMapped($attribute)) {
                $attributeLabels = $this->getValuesLabels($attribute, $attributeValues, $storeId);
                $retrievedLabel = $this->retrieveFieldValue($attributeLabels);
                if ($retrievedLabel) {
                    $productAttributes[$attribute->getAttributeCode() . '_value'] = $retrievedLabel;
                }
            }
        }

        return $productAttributes;
    }

    /**
     * @param array $values
     * @return array
     */
    private function prepareMultiselectValues(array $values): array
    {
        return \array_merge(
            ...\array_map(
                function (string $value) {
                    return \explode(',', $value);
                },
                $values
            )
        );
    }

    /**
     * @param array $values
     * @return array|mixed|null
     */
    private function retrieveFieldValue(array $values): mixed
    {
        $values = array_unique($values);
        return count($values) === 1 ? array_shift($values) : array_values($values);
    }

    /**
     * @param Attribute $attribute
     * @return bool
     */
    private function isAttributeLabelsShouldBeMapped(Attribute $attribute): bool
    {
        return (
            $attribute->getIsSearchable()
            || $attribute->getIsVisibleInAdvancedSearch()
            || $attribute->getIsFilterable()
            || $attribute->getIsFilterableInSearch()
        );
    }

    /**
     * @param Attribute $attribute
     * @param array $attributeValues
     * @param $storeId
     * @return array
     */
    private function getValuesLabels(Attribute $attribute, array $attributeValues, $storeId): array
    {
        $attributeLabels = [];

        $options = $this->getAttributeOptions($attribute, $storeId);
        if (empty($options)) {
            return $attributeLabels;
        }

        foreach ($options as $option) {
            if (\in_array($option['value'], $attributeValues)) {
                $attributeLabels[] = $option['label'];
            }
        }

        return $attributeLabels;
    }

    /**
     * @param Attribute $attribute
     * @param $storeId
     * @return array
     */
    private function getAttributeOptions(Attribute $attribute, $storeId): array
    {
        if (!isset($this->attributeOptionsCache[$storeId][$attribute->getId()])) {
            $attributeStoreId = $attribute->getStoreId();
            $options = $attribute->usesSource() ? $attribute->setStoreId($storeId)->getSource()->getAllOptions() : [];
            $this->attributeOptionsCache[$storeId][$attribute->getId()] = $options;
            $attribute->setStoreId($attributeStoreId);
        }

        return $this->attributeOptionsCache[$storeId][$attribute->getId()];
    }
}
