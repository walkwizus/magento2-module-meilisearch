<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryFacet;

class FacetFilterBuilder
{
    /**
     * @param array $filters
     * @param string|null $excludedCode
     * @return string
     */
    public function build(array $filters, ?string $excludedCode = null): string
    {
        $expressions = [];

        foreach ($filters as $code => $values) {
            $code = (string) $code;

            if ($code === $excludedCode || !preg_match('/^[A-Za-z0-9_.]+$/', $code) || !is_array($values) || $values === []) {
                continue;
            }

            $valueExpressions = array_map(fn (mixed $value): string => $this->buildValueExpression($code, (string) $value), array_values($values));
            $expressions[] = count($valueExpressions) === 1 ? $valueExpressions[0] : '(' . implode(' OR ', $valueExpressions) . ')';
        }

        return implode(' AND ', $expressions);
    }

    /**
     * @param string $code
     * @param string $value
     * @return string
     */
    private function buildValueExpression(string $code, string $value): string
    {
        if (preg_match('/^(\d+(?:\.\d+)?)_(\d+(?:\.\d+)?)$/', $value, $matches)) {
            return sprintf('(%s >= %s AND %s <= %s)', $code, $matches[1], $code, $matches[2]);
        }

        return sprintf('%s = "%s"', $code, str_replace(['\\', '"'], ['\\\\', '\\"'], $value));
    }
}
