<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Block\Layer;

use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Framework\View\Element\Template;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Helper\Media as SwatchMediaHelper;

class SwatchRenderer extends Template
{
    private ?array $renderData = null;

    public function __construct(
        Template\Context $context,
        private readonly AttributeFactory $attributeFactory,
        private readonly SwatchHelper $swatchHelper,
        private readonly SwatchMediaHelper $swatchMediaHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array{
     *     attribute_code:string,
     *     attribute_id:int,
     *     items:array<int, array{
     *         option_id:string,
     *         raw_value:string,
     *         label:string,
     *         count:int,
     *         link:string,
     *         is_active:bool,
     *         custom_style:string,
     *         swatch_type:string,
     *         swatch_value:string
     *     }>,
     *     swatches:array<string, array<string, mixed>>
     * }
     */
    public function getRenderData(): array
    {
        if ($this->renderData !== null) {
            return $this->renderData;
        }

        $facet = $this->getFacet();
        $options = $this->getFacetOptions();
        $params = $this->getParams();
        $facetCode = (string)($facet['code'] ?? '');

        $emptyData = [
            'attribute_code' => $facetCode,
            'attribute_id' => 0,
            'items' => [],
            'swatches' => []
        ];

        if ($facetCode === '' || $options === []) {
            $this->renderData = $emptyData;
            return $this->renderData;
        }

        $attribute = $this->attributeFactory->create()->loadByCode('catalog_product', $facetCode);
        if (!$attribute->getId() || !$this->swatchHelper->isSwatchAttribute($attribute)) {
            $this->renderData = $emptyData;
            return $this->renderData;
        }

        $selected = isset($params[$facetCode]) && is_string($params[$facetCode]) ? (string)$params[$facetCode] : '';
        $lookup = $this->buildAttributeOptionLookup($attribute->getOptions());

        $rows = [];
        $optionIds = [];
        foreach ($options as $rawValue => $count) {
            $raw = (string)$rawValue;
            $parsedRawSwatch = $this->parseRawSwatchFacetValue($raw);
            $resolvedOption = $this->resolveOption($lookup, $raw, $parsedRawSwatch['label']);

            $optionId = $resolvedOption['id'] ?? '';
            $label = $resolvedOption['label'] ?? ($parsedRawSwatch['label'] !== '' ? $parsedRawSwatch['label'] : $raw);

            $query = $params;
            $query[$facetCode] = $raw;
            $link = (string)$this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);

            $rows[] = [
                'option_id' => $optionId,
                'raw_value' => $raw,
                'label' => $label,
                'count' => (int)$count,
                'link' => $link,
                'is_active' => $selected === $raw,
                'custom_style' => (int)$count > 0 ? '' : 'disabled',
                'parsed_swatch_type' => $parsedRawSwatch['type'],
                'parsed_swatch_value' => $parsedRawSwatch['value']
            ];

            if ($optionId !== '') {
                $optionIds[] = $optionId;
            }
        }

        $swatches = [];
        if ($optionIds !== []) {
            $swatches = $this->swatchHelper->getSwatchesByOptionsId(array_values(array_unique($optionIds)));
        }

        $items = [];
        foreach ($rows as $row) {
            $optionId = (string)$row['option_id'];
            $swatchType = '';
            $swatchValue = '';

            if ($optionId !== '' && isset($swatches[$optionId]) && is_array($swatches[$optionId])) {
                $swatchType = (string)($swatches[$optionId]['type'] ?? '');
                $swatchValue = (string)($swatches[$optionId]['value'] ?? '');
            }

            if ($swatchType === '' && $row['parsed_swatch_type'] !== '') {
                $swatchType = (string)$row['parsed_swatch_type'];
                $swatchValue = (string)$row['parsed_swatch_value'];
            }

            $row['swatch_type'] = $swatchType;
            $row['swatch_value'] = $swatchValue;
            unset($row['parsed_swatch_type'], $row['parsed_swatch_value']);
            $items[] = $row;
        }

        $this->renderData = [
            'attribute_code' => (string)$facetCode,
            'attribute_id' => (int)$attribute->getId(),
            'items' => $items,
            'swatches' => $swatches
        ];

        return $this->renderData;
    }

    public function getSwatchPath(string $type, string $filename): string
    {
        return $this->swatchMediaHelper->getSwatchAttributeImage($type, $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFacet(): array
    {
        $facet = $this->getData('facet');
        return is_array($facet) ? $facet : [];
    }

    /**
     * @return array<string, int|string>
     */
    private function getFacetOptions(): array
    {
        $options = $this->getData('options');
        return is_array($options) ? $options : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function getParams(): array
    {
        $params = $this->getData('params');
        return is_array($params) ? $params : [];
    }

    /**
     * @param array<int, mixed> $attributeOptions
     * @return array{
     *     byId: array<string, array{id:string,label:string}>,
     *     byLabel: array<string, array{id:string,label:string}>
     * }
     */
    private function buildAttributeOptionLookup(array $attributeOptions): array
    {
        $byId = [];
        $byLabel = [];

        foreach ($attributeOptions as $option) {
            if (!is_object($option) || !method_exists($option, 'getValue') || !method_exists($option, 'getLabel')) {
                continue;
            }

            $id = (string)$option->getValue();
            $label = trim((string)$option->getLabel());
            if ($id === '' || $label === '') {
                continue;
            }

            $normalizedLabel = strtolower($label);

            $entry = ['id' => $id, 'label' => $label];
            $byId[$id] = $entry;
            if (!isset($byLabel[$normalizedLabel])) {
                $byLabel[$normalizedLabel] = $entry;
            }
        }

        return ['byId' => $byId, 'byLabel' => $byLabel];
    }

    /**
     * @param array{
     *     byId: array<string, array{id:string,label:string}>,
     *     byLabel: array<string, array{id:string,label:string}>
     * } $lookup
     * @return array{id:string,label:string}|null
     */
    private function resolveOption(array $lookup, string $rawValue, string $parsedLabel = ''): ?array
    {
        if (isset($lookup['byId'][$rawValue])) {
            return $lookup['byId'][$rawValue];
        }

        $normalized = strtolower(trim($rawValue));
        if ($normalized !== '' && isset($lookup['byLabel'][$normalized])) {
            return $lookup['byLabel'][$normalized];
        }

        $normalizedParsedLabel = strtolower(trim($parsedLabel));
        if ($normalizedParsedLabel !== '' && isset($lookup['byLabel'][$normalizedParsedLabel])) {
            return $lookup['byLabel'][$normalizedParsedLabel];
        }

        return null;
    }

    /**
     * @return array{label:string,type:string,value:string}
     */
    private function parseRawSwatchFacetValue(string $rawValue): array
    {
        $matches = [];
        if (preg_match('/^(.*)\|([0-3])\|(.*)$/', $rawValue, $matches) !== 1) {
            return ['label' => '', 'type' => '', 'value' => ''];
        }

        return [
            'label' => trim((string)$matches[1]),
            'type' => (string)$matches[2],
            'value' => (string)$matches[3]
        ];
    }
}
