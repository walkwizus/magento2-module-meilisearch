<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Service;

use Walkwizus\MeilisearchMerchandising\Model\AttributeRuleProvider;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;

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
     */
    public function __construct(
        private readonly AttributeRuleProvider $attributeRuleProvider,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly AttributeResolver $attributeResolver
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
        } elseif (is_numeric($val)) {
            return $val;
        } else {
            return "\"$val\"";
        }
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
                    $rule['type'] = 'integer';
                    $rule['input'] = 'select';
                    $rule['operators'] = ['in', 'not_in'];
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSelectValues($attributeCode): array
    {
        $values = [];
        $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);
        $options = $attribute->getSource()->getAllOptions();

        foreach ($options as $option) {
            if (isset($option['value'])) {
                $values[$option['value']] = $option['label'];
            }
        }

        return $values;
    }
}
