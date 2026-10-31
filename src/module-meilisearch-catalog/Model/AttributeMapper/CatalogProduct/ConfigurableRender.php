<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper\CatalogProduct;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Zend_Db_Expr;

class ConfigurableRender implements AttributeMapperInterface
{
    private const IMAGE_ATTRIBUTES = [
        'image',
        'small_image',
        'thumbnail',
    ];

    private const CHILD_PRICE_FIELDS = [
        'price',
        'final_price',
        'has_discount',
    ];

    /**
     * @param ResourceConnection $resource
     * @param MetadataPool $metadataPool
     * @param EavConfig $eavConfig
     * @param Inventory $inventory
     * @param PriceRender $priceRender
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly MetadataPool $metadataPool,
        private readonly EavConfig $eavConfig,
        private readonly Inventory $inventory,
        private readonly PriceRender $priceRender
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        $parentIds = array_values(array_filter(array_map('intval', array_keys($documentData))));
        if ($parentIds === []) {
            return [];
        }

        $storeId = (int) $storeId;
        $superAttributes = $this->fetchSuperAttributes($parentIds, $storeId);
        if ($superAttributes === []) {
            return [];
        }

        $childrenByParent = $this->fetchChildrenByParent(array_keys($superAttributes));
        $childIds = array_values(array_unique(array_merge([], ...array_values($childrenByParent))));
        if ($childIds === []) {
            return [];
        }

        $attributeIds = array_values(array_unique(array_merge([], ...array_map('array_keys', $superAttributes))));
        $childValues = $this->fetchChildAttributeValues($childIds, $attributeIds, $storeId);
        $stockByChild = $this->inventory->map(array_fill_keys($childIds, []), $storeId);
        $imagesByChild = $this->fetchImagesByChild($childIds, $storeId);
        $pricesByChild = $this->fetchPricesByChild($childIds, $storeId);

        $result = [];
        foreach ($childrenByParent as $parentId => $children) {
            $attributes = $superAttributes[$parentId];
            $renderChildren = [];

            foreach ($children as $childId) {
                $values = array_intersect_key($childValues[$childId] ?? [], $attributes);
                if (count($values) !== count($attributes)) {
                    continue;
                }

                $renderChildren[] = [
                    'id' => $childId,
                    'attributes' => $this->sortValues($values, $attributes),
                    'is_available' => (bool) ($stockByChild[$childId]['stock']['is_in_stock'] ?? false),
                    'images' => $imagesByChild[$childId] ?? [],
                    'price_render' => $pricesByChild[$childId] ?? [],
                ];
            }

            if ($renderChildren !== []) {
                $result[$parentId]['configurable_render'] = [
                    'attributes' => array_values($attributes),
                    'children' => $renderChildren,
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $parentIds
     * @param int $storeId
     * @return array
     * @throws \Exception
     */
    private function fetchSuperAttributes(array $parentIds, int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $linkField = $this->getLinkField();
        $labelTable = $this->resource->getTableName('catalog_product_super_attribute_label');

        $select = $connection->select()
            ->from(['parent' => $this->resource->getTableName('catalog_product_entity')], ['parent_id' => 'entity_id'])
            ->join(
                ['cpsa' => $this->resource->getTableName('catalog_product_super_attribute')],
                'cpsa.product_id = parent.' . $linkField,
                ['attribute_id']
            )
            ->join(
                ['ea' => $this->resource->getTableName('eav_attribute')],
                'ea.attribute_id = cpsa.attribute_id',
                ['attribute_code']
            )
            ->joinLeft(
                ['label_store' => $labelTable],
                'label_store.product_super_attribute_id = cpsa.product_super_attribute_id'
                . $connection->quoteInto(' AND label_store.store_id = ?', $storeId),
                []
            )
            ->joinLeft(
                ['label_default' => $labelTable],
                'label_default.product_super_attribute_id = cpsa.product_super_attribute_id'
                . ' AND label_default.store_id = 0',
                []
            )
            ->columns([
                'label' => new Zend_Db_Expr(
                    'COALESCE(label_store.value, label_default.value, ea.frontend_label, ea.attribute_code)'
                ),
            ])
            ->where('parent.entity_id IN (?)', $parentIds)
            ->order(['parent.entity_id ASC', 'cpsa.position ASC', 'cpsa.attribute_id ASC']);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $attributeId = (int) $row['attribute_id'];
            $result[(int) $row['parent_id']][$attributeId] = [
                'attribute_id' => $attributeId,
                'attribute_code' => (string) $row['attribute_code'],
                'label' => (string) $row['label'],
            ];
        }

        return $result;
    }

    /**
     * @param array $parentIds
     * @return array
     * @throws \Exception
     */
    private function fetchChildrenByParent(array $parentIds): array
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(['parent' => $this->resource->getTableName('catalog_product_entity')], ['parent_id' => 'entity_id'])
            ->join(
                ['link' => $this->resource->getTableName('catalog_product_super_link')],
                'link.parent_id = parent.' . $this->getLinkField(),
                ['child_id' => 'product_id']
            )
            ->where('parent.entity_id IN (?)', $parentIds)
            ->order(['parent.entity_id ASC', 'link.product_id ASC']);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int) $row['parent_id']][] = (int) $row['child_id'];
        }

        return $result;
    }

    /**
     * @param array $childIds
     * @param array $attributeIds
     * @param int $storeId
     * @return array
     * @throws \Exception
     */
    private function fetchChildAttributeValues(array $childIds, array $attributeIds, int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $linkField = $this->getLinkField();

        $select = $connection->select()
            ->from(['product' => $this->resource->getTableName('catalog_product_entity')], ['entity_id'])
            ->join(
                ['cpei' => $this->resource->getTableName('catalog_product_entity_int')],
                'cpei.' . $linkField . ' = product.' . $linkField,
                ['attribute_id', 'value']
            )
            ->where('product.entity_id IN (?)', $childIds)
            ->where('cpei.attribute_id IN (?)', $attributeIds)
            ->where('cpei.store_id IN (?)', array_unique([0, $storeId]))
            ->order('cpei.store_id ASC');

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $optionId = (int) $row['value'];
            if ($optionId !== 0) {
                $result[(int) $row['entity_id']][(int) $row['attribute_id']] = $optionId;
            }
        }

        return $result;
    }

    /**
     * @param array $childIds
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function fetchImagesByChild(array $childIds, int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $linkField = $this->getLinkField();
        $valueTable = $this->resource->getTableName('catalog_product_entity_varchar');

        $select = $connection->select()
            ->from(['product' => $this->resource->getTableName('catalog_product_entity')], ['entity_id'])
            ->join(
                ['ea' => $this->resource->getTableName('eav_attribute')],
                $connection->quoteInto('ea.attribute_code IN (?)', self::IMAGE_ATTRIBUTES)
                . $connection->quoteInto(
                    ' AND ea.entity_type_id = ?',
                    (int) $this->eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)->getEntityTypeId()
                ),
                ['attribute_code']
            )
            ->join(
                ['default_value' => $valueTable],
                'default_value.' . $linkField . ' = product.' . $linkField
                . ' AND default_value.attribute_id = ea.attribute_id'
                . ' AND default_value.store_id = 0',
                []
            )
            ->joinLeft(
                ['store_value' => $valueTable],
                'store_value.' . $linkField . ' = product.' . $linkField
                . ' AND store_value.attribute_id = ea.attribute_id'
                . $connection->quoteInto(' AND store_value.store_id = ?', $storeId),
                ['value' => new Zend_Db_Expr('COALESCE(store_value.value, default_value.value)')]
            )
            ->where('product.entity_id IN (?)', $childIds);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $value = (string) ($row['value'] ?? '');
            if ($value !== '' && $value !== 'no_selection') {
                $result[(int) $row['entity_id']][(string) $row['attribute_code']] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array $childIds
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    private function fetchPricesByChild(array $childIds, int $storeId): array
    {
        $result = [];
        foreach ($this->priceRender->map(array_fill_keys($childIds, []), $storeId) as $childId => $data) {
            foreach ((array) ($data['price_render'] ?? []) as $priceAttribute => $currencies) {
                foreach ((array) $currencies as $currencyCode => $prices) {
                    $result[(int) $childId][$priceAttribute][$currencyCode] = array_intersect_key(
                        (array) $prices,
                        array_flip(self::CHILD_PRICE_FIELDS)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param array $values
     * @param array $attributes
     * @return array
     */
    private function sortValues(array $values, array $attributes): array
    {
        $sorted = [];
        foreach (array_keys($attributes) as $attributeId) {
            $sorted[(string) $attributeId] = $values[$attributeId];
        }

        return $sorted;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }
}
