<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper\CatalogProduct;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\EntityManager\MetadataPool;

class Image implements AttributeMapperInterface
{
    const XML_PATH_CATALOG_PLACEHOLDER_IMAGE_PLACEHOLDER = 'catalog/placeholder/image_placeholder';
    const IMAGE_PATH_PLACEHOLDER = '/placeholder';

    /**
     * @var string[]
     */
    private array $imagesAttributes = [
        'image',
        'small_image',
        'thumbnail',
    ];

    /**
     * @var array<int, string>|null
     */
    private ?array $imageAttributeCodesById = null;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly MetadataPool $metadataPool
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @param array $context
     * @return array
     * @throws LocalizedException
     */
    public function map(array $documentData, $storeId, array $context = []): array
    {
        $documents = [];
        $storeId = (int) $storeId;
        $placeholder = $this->getPlaceholder($storeId);
        $imagesByProduct = $this->getProductsImagesByCode(array_map('intval', array_keys($documentData)), $storeId);

        foreach ($documentData as $id => $indexData) {
            $images = $imagesByProduct[(int) $id] ?? [];

            foreach ($this->imagesAttributes as $code) {
                $documents[$id][$code] = $this->normalizeImageValue($images[$code] ?? null, $placeholder);
            }
        }

        return $documents;
    }

    /**
     * @param array $productIds
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    protected function getProductsImagesByCode(array $productIds, int $storeId): array
    {
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $attributeCodesById = $this->getImageAttributeCodesById();
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();

        $select = $connection->select()
            ->from(
                ['cpev' => $connection->getTableName('catalog_product_entity_varchar')],
                ['product_id' => 'cpev.' . $linkField, 'attribute_id' => 'cpev.attribute_id', 'value' => 'cpev.value']
            )
            ->where('cpev.' . $linkField . ' IN (?)', $productIds)
            ->where('cpev.attribute_id IN (?)', array_keys($attributeCodesById))
            ->where('cpev.store_id IN (?)', array_unique([Store::DEFAULT_STORE_ID, $storeId]))
            ->order('cpev.store_id ' . Select::SQL_ASC);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $code = $attributeCodesById[(int) $row['attribute_id']] ?? null;
            if ($code !== null) {
                $result[(int) $row['product_id']][$code] = (string) $row['value'];
            }
        }

        return $result;
    }

    /**
     * @return array|string[]
     * @throws LocalizedException
     */
    private function getImageAttributeCodesById(): array
    {
        if ($this->imageAttributeCodesById === null) {
            $this->imageAttributeCodesById = [];
            foreach ($this->imagesAttributes as $code) {
                $attribute = $this->eavConfig->getAttribute(ProductAttributeInterface::ENTITY_TYPE_CODE, $code);
                if ($attribute->getId()) {
                    $this->imageAttributeCodesById[(int) $attribute->getId()] = $code;
                }
            }
        }

        return $this->imageAttributeCodesById;
    }

    /**
     * @param string|null $value
     * @param string $placeholder
     * @return string
     */
    private function normalizeImageValue(?string $value, string $placeholder): string
    {
        $value = (string) $value;

        if ($value === '' || $value === 'no_selection') {
            return $placeholder;
        }

        return $value;
    }

    /**
     * @param int $storeId
     * @return string
     */
    protected function getPlaceholder(int $storeId): string
    {
        $placeholder = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CATALOG_PLACEHOLDER_IMAGE_PLACEHOLDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($placeholder === '') {
            $placeholder = 'image.jpg';
        }

        return self::IMAGE_PATH_PLACEHOLDER . '/' . $placeholder;
    }
}
