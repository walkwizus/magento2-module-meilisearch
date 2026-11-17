<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Model\AttributeMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchSearchAdapter\Model\AttributeProvider\CatalogProduct\OptionIds as OptionIdsProvider;

class OptionIds implements AttributeMapperInterface
{
    /**
     * Not merged from a configurable's children.
     */
    private const OWN_VALUE_ONLY = ['status', 'visibility', 'tax_class_id'];

    /**
     * @param OptionIdsProvider $optionIdsProvider
     */
    public function __construct(
        private readonly OptionIdsProvider $optionIdsProvider
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        $attributes = $this->optionIdsProvider->getAttributes();
        $documents = [];

        foreach ($documentData as $productId => $indexData) {
            foreach (array_intersect_key($indexData, $attributes) as $attributeId => $values) {
                $code = $attributes[$attributeId]->getAttributeCode();
                $values = is_array($values) ? $values : [$productId => $values];

                if (in_array($code, self::OWN_VALUE_ONLY, true)) {
                    $values = [$values[$productId] ?? ''];
                }

                // source models can use non-numeric values (country_of_manufacture: "US")
                $ids = array_unique(array_map(
                    fn ($id) => ctype_digit($id) ? (int)$id : $id,
                    array_filter(explode(',', implode(',', $values)), fn ($id) => $id !== '')
                ));

                if ($ids) {
                    $documents[$productId][$code . OptionIdsProvider::SUFFIX] = array_values($ids);
                }
            }
        }

        return $documents;
    }
}
