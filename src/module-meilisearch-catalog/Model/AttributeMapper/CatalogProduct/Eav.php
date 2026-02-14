<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Walkwizus\MeilisearchBase\Service\Translation;
use Magento\Store\Model\ScopeInterface;
use Walkwizus\MeilisearchCatalog\Model\Indexer\PreProcessor\CatalogProduct\UrlKey;

class Eav implements AttributeMapperInterface
{
    private const PRODUCT_URL_FALLBACK_PATTERN = 'catalog/product/view/id/%d';

    /**
     * @var array|string[]
     */
    private array $defaultExcludedAttributes = [
        'price',
        'media_gallery',
        'tier_price',
        'quantity_and_stock_status',
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
    private array $attributeParentProduct = [
        'name',
        'url_key',
    ];

    /**
     * @var AttributeOptionInterface[]
     */
    private array $attributeOptionsCache = [];

    /**
     * @var array|string[]
     */
    private array $excludedAttributes;

    /**
     * @var string|null
     */
    private ?string $locale = null;

    /**
     * @param DataProvider $dataProvider
     * @param SwatchHelper $swatchHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Translation $translation
     * @param array $excludedAttributes
     */
    public function __construct(
        private readonly DataProvider $dataProvider,
        private readonly SwatchHelper $swatchHelper,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Translation $translation,
        array $excludedAttributes = []
    ) {
        $this->excludedAttributes = array_merge($this->defaultExcludedAttributes, $excludedAttributes);
    }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        $documents = [];

        $this->locale = $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        try {
            $storeId = (int) $storeId;
            $productUrlKeys = $this->getProductUrlKeysFromContext($context);

            foreach ($documentData as $productId => $indexData) {
                $productIndexData = $this->convertToProductData((int)$productId, $indexData, $storeId);
                $productIndexData['url_key'] = $productUrlKeys[(int) $productId]
                    ?? sprintf(self::PRODUCT_URL_FALLBACK_PATTERN, (int) $productId);

                $documents[$productId] = ['id' => $productId];
                foreach ($productIndexData as $attributeCode => $value) {
                    $documents[$productId][$attributeCode] = $value;
                }
            }
        } catch(\Exception $e) {

        }

        return $documents;
    }

    /**
     * @param int $productId
     * @param array $indexData
     * @param int|string $storeId
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
     * @return array
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

        if (in_array($attribute->getAttributeCode(), $this->attributeParentProduct) && count($attributeValues) > 1) {
            $attributeValues = [$attributeValues[$productId] ?? array_shift($attributeValues)];
        }

        return $attributeValues;
    }

    /**
     * @param Attribute $attribute
     * @param array $attributeValues
     * @param int|string $storeId
     * @return array
     */
    private function convertAttribute(Attribute $attribute, array $attributeValues, $storeId): array
    {
        $productAttributes = [];
        $attributeCode = $attribute->getAttributeCode();

        if ($attribute->usesSource()) {
            $isSwatch = $this->swatchHelper->isSwatchAttribute($attribute);
            $options = $this->getAttributeOptions($attribute, $storeId);

            $swatchData = $isSwatch ? $this->swatchHelper->getSwatchesByOptionsId(array_column($options, 'value')) : [];

            $finalValues = [];
            foreach ($options as $option) {
                if (in_array($option['value'], $attributeValues)) {
                    $label = $option['label'];

                    if ($attribute->getFrontendInput() === 'boolean') {
                        $label = $this->translation->translateByLangCode((string)$label, $this->locale);
                    }

                    if ($isSwatch && isset($swatchData[$option['value']])) {
                        $swatch = $swatchData[$option['value']];
                        $type = $swatch['type'];
                        $value = $swatch['value'];

                        $finalValues[] = "{$label}|{$type}|{$value}";
                    } else {
                        $finalValues[] = (string)$label;
                    }
                }
            }

            $result = $this->retrieveFieldValue($finalValues);
            if ($result !== null) {
                $productAttributes[$attributeCode] = $result;
                return $productAttributes;
            }
        }

        $retrievedValue = $this->retrieveFieldValue($attributeValues);
        if ($retrievedValue !== null) {
            $productAttributes[$attributeCode] = $retrievedValue;
        }

        return $productAttributes;
    }

    /**
     * @param array $values
     * @return array
     */
    private function prepareMultiselectValues(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $result = array_merge($result, explode(',', $value));
            } elseif (is_array($value)) {
                $result = array_merge($result, $value);
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * @param array $values
     * @return mixed
     */
    private function retrieveFieldValue(array $values): mixed
    {
        $values = array_filter(array_unique($values), function($v) {
            return $v !== null && $v !== '';
        });

        if (empty($values)) {
            return null;
        }

        return count($values) === 1 ? array_shift($values) : array_values($values);
    }

    /**
     * @param Attribute $attribute
     * @param int $storeId
     * @return array
     */
    private function getAttributeOptions(Attribute $attribute, $storeId): array
    {
        $cacheKey = $storeId . '_' . $attribute->getId();
        if (!isset($this->attributeOptionsCache[$cacheKey])) {
            $options = $attribute->setStoreId($storeId)->getSource()->getAllOptions();
            $this->attributeOptionsCache[$cacheKey] = $options;
        }

        return $this->attributeOptionsCache[$cacheKey];
    }

    /**
     * @param array $context
     * @return array<int, string>
     */
    private function getProductUrlKeysFromContext(array $context): array
    {
        $productUrlKeys = $context[UrlKey::CONTEXT_PRODUCT_URL_KEYS] ?? [];
        if (!is_array($productUrlKeys)) {
            return [];
        }

        return array_map('strval', $productUrlKeys);
    }
}
