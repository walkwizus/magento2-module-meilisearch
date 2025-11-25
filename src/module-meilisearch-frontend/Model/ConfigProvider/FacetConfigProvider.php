<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Swatches\Model\Swatch;
use Magento\Framework\Exception\LocalizedException;

class FacetConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array|string[]
     */
    private array $regionMapping = [
        'select' => 'checkbox',
        'multiselect' => 'checkbox',
        'boolean' => 'checkbox',
        'price' => 'price',
        'swatch_visual' => 'swatch',
        'swatch_text' => 'swatch'
    ];

    /**
     * @param AttributeProvider $attributeProvider
     * @param AttributeResolver $attributeResolver
     * @param AttributeFactory $attributeFactory
     * @param SwatchHelper $swatchHelper
     */
    public function __construct(
        private readonly AttributeProvider $attributeProvider,
        private readonly AttributeResolver $attributeResolver,
        private readonly AttributeFactory $attributeFactory,
        private readonly SwatchHelper $swatchHelper
    ) { }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function get(): array
    {
        $attributes = $this->attributeProvider->getFilterableAttributes(SearchIndexNameResolver::DEFAULT_INDEX, 'frontend');
        $processedFacets = $this->getFacetList($attributes);

        return [
            'facets' => [
                'facetList' => array_keys($processedFacets),
                'facetConfig' => $processedFacets
            ]
        ];
    }

    /**
     * @param array $attributes
     * @return array
     * @throws LocalizedException
     */
    public function getFacetList(array $attributes): array
    {
        $processedFacets = [];

        foreach ($attributes as $attributeCode) {
            $attributeConfig = $this->getAttributeConfig($attributeCode);
            if ($attributeConfig) {
                $resolvedCode = $this->attributeResolver->resolve($attributeCode);
                $processedFacets[$resolvedCode] = $attributeConfig;
            }
        }

        return $processedFacets;
    }

    /**
     * @param string $attributeCode
     * @return array|null
     * @throws LocalizedException
     */
    private function getAttributeConfig(string $attributeCode): ?array
    {
        $attribute = $this->attributeFactory->create()->loadByCode('catalog_product', $attributeCode);

        if (!$attribute->getId()) {
            return null;
        }

        $frontendInput = $attribute->getFrontendInput();
        $renderRegion = $this->determineRenderRegion($attribute);
        $useSource = $attribute->usesSource();

        $config = [
            'code' => $this->attributeResolver->resolve($attributeCode),
            'position' => (int)$attribute->getPosition(),
            'showMore' => (bool)$attribute->getMeilisearchShowMore(),
            'showMoreLimit' => (int)$attribute->getMeilisearchShowMoreLimit(),
            'searchable' => (bool)$attribute->getMeilisearchSearchable(),
            'searchboxFuzzyEnabled' => (bool)$attribute->getMeilisearchSearchboxFuzzyEnabled(),
            'sortValuesBy' => $attribute->getMeilisearchSortValuesBy(),
            'label' => $attribute->getStoreLabel(),
            'type' => $frontendInput,
            'renderRegion' => $renderRegion,
            'hasOptions' => $useSource
        ];

        if ($useSource) {
            $config = $this->addSourceOptions($config, $attribute);
        }

        return $config;
    }

    /**
     * @param Attribute $attribute
     * @return string
     */
    private function determineRenderRegion(Attribute $attribute): string
    {
        if ($this->swatchHelper->isSwatchAttribute($attribute)) {
            $swatchType = $attribute->getData(Swatch::SWATCH_INPUT_TYPE_KEY);
            return $this->regionMapping["swatch_" . $swatchType] ?? 'checkbox';
        }

        $frontendInput = $attribute->getFrontendInput();

        return $this->regionMapping[$frontendInput] ?? 'checkbox';
    }

    /**
     * @param array $config
     * @param Attribute $attribute
     * @return array
     * @throws LocalizedException
     */
    private function addSourceOptions(array $config, Attribute $attribute): array
    {
        if ($this->swatchHelper->isSwatchAttribute($attribute)) {
            $config['frontendInput'] = $attribute->getData('frontend_input');
            $config['swatchInputType'] = $attribute->getData(Swatch::SWATCH_INPUT_TYPE_KEY);
        }

        $allOptions = $attribute->getSource()->getAllOptions();
        $config['options'] = [];

        $swatchData = [];
        if ($this->swatchHelper->isSwatchAttribute($attribute)) {
            $optionIds = array_column($allOptions, 'value');
            $swatchData = $this->swatchHelper->getSwatchesByOptionsId($optionIds);
        }

        foreach ($allOptions as $option) {
            $optionId = $option['value'];

            if ($optionId === '') {
                continue;
            }

            $optionConfig = $this->buildOptionConfig($option, $optionId, $swatchData[$optionId] ?? null);
            $config['isSwatch'] = $this->swatchHelper->isSwatchAttribute($attribute);
            $config['options'][$optionId] = $optionConfig;
        }

        return $config;
    }

    /**
     * @param array $option
     * @param mixed $optionId
     * @param array|null $swatchData
     * @return array
     */
    private function buildOptionConfig(array $option, $optionId, ?array $swatchData): array
    {
        $optionConfig = [
            'label' => $option['label'],
            'value' => $optionId
        ];

        if ($swatchData) {
            $optionConfig['swatchType'] = (int)$swatchData['type'];
            $optionConfig['swatchValue'] = $this->formatSwatchValue($swatchData);
        }

        return $optionConfig;
    }

    /**
     * @param array $swatch
     * @return string
     */
    private function formatSwatchValue(array $swatch): string
    {
        if ($swatch['type'] == Swatch::SWATCH_TYPE_VISUAL_COLOR) {
            return $swatch['value'] && $swatch['value'][0] !== '#' ? '#' . $swatch['value'] : $swatch['value'];
        }

        return $swatch['value'];
    }
}
