<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Walkwizus\MeilisearchMerchandising\Model\AttributeRuleProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Framework\Exception\NoSuchEntityException;

class QueryBuilderService
{
    /**
     * @var array
     */
    private array $operatorMapper = [
        'equal' => '=',
        'not_equal' => '!=',
        'in' => 'IN',
        'not_in' => 'NOT IN',
        'less' => '<',
        'less_or_equal' => '<=',
        'greater' => '>',
        'greater_or_equal' => '>=',
        'between' => 'TO',
        'is_null' => 'IS NULL',
        'is_not_null' => 'IS NOT NULL'
    ];

    /**
     * @param AttributeRuleProvider $attributeRuleProvider
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeResolver $attributeResolver
     * @param SwatchHelper $swatchHelper
     */
    public function __construct(
        private readonly AttributeRuleProvider $attributeRuleProvider,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly AttributeResolver $attributeResolver,
        private readonly SwatchHelper $swatchHelper
    ) { }

    /**
     * @param array $rule
     * @return string
     */
    public function convertRulesToMeilisearchQuery(array $rule): string
    {
        return $this->buildQuery($rule);
    }

    /**
     * @param array $documentData
     * @param array $rule
     * @return bool
     */
    public function isMatch(array $documentData, array $rule): bool
    {
        $condition = $rule['condition'] ?? 'AND';

        if (isset($rule['rules']) && is_array($rule['rules'])) {
            $results = [];
            foreach ($rule['rules'] as $subRule) {
                $results[] = $this->isMatch($documentData, $subRule);
            }

            return ($condition === 'OR')
                ? in_array(true, $results, true)
                : !in_array(false, $results, true);
        }

        $field = $this->attributeResolver->resolve($rule['field']);
        $currentValue = $documentData[$field] ?? null;
        $operator = $rule['operator'];
        $targetValue = $rule['value'];

        return $this->evaluateCondition($currentValue, $operator, $targetValue);
    }

    /**
     * @param $currentValue
     * @param string $operator
     * @param $targetValue
     * @return bool
     */
    private function evaluateCondition($currentValue, string $operator, $targetValue): bool
    {
        $currentValues = is_array($currentValue) ? $currentValue : [$currentValue];
        $targetValues = is_array($targetValue) ? $targetValue : [$targetValue];

        switch ($operator) {
            case 'in':
                foreach ($currentValues as $cVal) {
                    foreach ($targetValues as $tVal) {
                        if ($this->compareValues($cVal, $tVal)) return true;
                    }
                }
                return false;

            case 'not_in':
                foreach ($currentValues as $cVal) {
                    foreach ($targetValues as $tVal) {
                        if ($this->compareValues($cVal, $tVal)) return false;
                    }
                }
                return true;

            case 'equal':
                return $this->compareValues($currentValue, $targetValue);

            case 'not_equal':
                return !$this->compareValues($currentValue, $targetValue);

            case 'less':
                return $currentValue < $targetValue;

            case 'less_or_equal':
                return $currentValue <= $targetValue;

            case 'greater':
                return $currentValue > $targetValue;

            case 'greater_or_equal':
                return $currentValue >= $targetValue;

            case 'between':
                if (!is_array($targetValue) || count($targetValue) < 2) return false;
                return $currentValue >= $targetValue[0] && $currentValue <= $targetValue[1];

            case 'is_null':
                return $currentValue === null || $currentValue === '';

            case 'is_not_null':
                return $currentValue !== null && $currentValue !== '';

            default:
                return false;
        }
    }

    /**
     * @param $currentValue
     * @param $targetValue
     * @return bool
     */
    private function compareValues($currentValue, $targetValue): bool
    {
        if ($currentValue == $targetValue) {
            return true;
        }

        if (is_string($targetValue) && str_contains($targetValue, '|')) {
            $parts = explode('|', $targetValue);
            return $currentValue == $parts[0];
        }

        return false;
    }

    /**
     * @param array $rule
     * @return string
     */
    private function buildQuery(array $rule): string
    {
        $meilisearchQuery = '';
        $condition = $rule['condition'] ?? 'AND';

        if (isset($rule['rules']) && is_array($rule['rules'])) {
            $subQueries = [];
            foreach ($rule['rules'] as $subRule) {
                $subQuery = $this->buildQuery($subRule);
                if (!empty($subQuery)) {
                    $subQueries[] = "($subQuery)";
                }
            }
            if (!empty($subQueries)) {
                $meilisearchQuery = implode(" $condition ", $subQueries);
            }
        } else {
            $field = $this->attributeResolver->resolve($rule['field']);
            $operator = $this->operatorMapper[$rule['operator']];
            $valueType = $rule['type'];

            if (in_array($operator, ['IN', 'NOT IN'])) {
                $values = is_array($rule['value']) ? $rule['value'] : [$rule['value']];
                $formattedValues = array_map(function ($val) use ($valueType) {
                    return $this->formatValue($val, $valueType);
                }, $values);
                $value = "[" . implode(", ", $formattedValues) . "]";
            } else {
                $value = $this->formatValue($rule['value'], $valueType);
            }

            $meilisearchQuery = "$field $operator $value";
        }

        return $meilisearchQuery;
    }

    /**
     * @param $val
     * @param $type
     * @return string|int|float
     */
    private function formatValue($val, $type): string|int|float
    {
        if ($type === 'boolean') {
            return $val ? '1' : '0';
        }

        if (is_numeric($val) && strpos((string)$val, '|') === false) {
            return $val;
        }

        $val = str_replace('"', '\"', (string)$val);
        return "\"$val\"";
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function convertAttributesToRules(): array
    {
        $attributes = $this->attributeRuleProvider->getAttributes();
        $rules = [];

        foreach ($attributes as $attribute) {
            $rule = [
                'id' => $attribute['code'],
                'label' => $attribute['label'],
                'operator' => 'equal',
            ];

            switch ($attribute['type']) {
                case 'text':
                    $rule['type'] = 'string';
                    $rule['operators'] = ['in', 'not_in'];
                    break;
                case 'select':
                case 'multiselect':
                    $rule['type'] = 'string';
                    $rule['input'] = 'select';
                    $rule['operators'] = ['in', 'not_in', 'equal', 'not_equal'];
                    $rule['multiple'] = true;
                    $rule['values'] = $this->getSelectValues($attribute['code']);
                    break;
                case 'boolean':
                    $rule['type'] = 'boolean';
                    $rule['input'] = 'radio';
                    $rule['values'] = [1 => 'Yes', 0 => 'No'];
                    break;
                case 'price':
                    $rule['type'] = 'double';
                    $rule['input'] = 'number';
                    break;
            }

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @param $attributeCode
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getSelectValues($attributeCode): array
    {
        $values = [];
        $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);
        $options = $attribute->getSource()->getAllOptions();

        $isSwatch = $this->swatchHelper->isSwatchAttribute($attribute);
        $swatches = [];
        if ($isSwatch) {
            $optionIds = array_column($options, 'value');
            $swatches = $this->swatchHelper->getSwatchesByOptionsId($optionIds);
        }

        foreach ($options as $option) {
            $optionId = $option['value'];
            if (!$optionId) continue;

            if ($isSwatch && isset($swatches[$optionId])) {
                $swatchData = $swatches[$optionId];
                $type = $swatchData['type'];
                $val = $swatchData['value'];
                $meiliValue = $option['label'] . '|' . $type . '|' . $val;
            } else {
                $meiliValue = $option['label'];
            }

            $values[$meiliValue] = $option['label'];
        }

        return $values;
    }
}
