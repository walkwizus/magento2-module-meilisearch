<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ResourceConnection;
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
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig,
        private readonly MetadataPool $metadataPool
    ) { }

    /**
     * @param array $documentData
     * @param int|string $storeId
     * @return array
     * @throws LocalizedException
     */
    public function map(array $documentData, $storeId): array
    {
        $documents = [];
        $storeId = (int) $storeId;

        foreach ($documentData as $id => $indexData) {
            $images = $this->getProductImagesByCode((int) $id);

            foreach ($this->imagesAttributes as $code) {
                $value = $images[$code] ?? null;
                $documents[$id][$code] = $this->normalizeImageValue($value, $storeId);
            }
        }

        return $documents;
    }

    /**
     * @param int $productId
     * @return array
     * @throws LocalizedException
     */
    protected function getProductImagesByCode(int $productId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $entityTypeId = (int) $this->eavConfig
            ->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();
        
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();

        $select = $connection->select()
            ->from(
                ['cpev' => $connection->getTableName('catalog_product_entity_varchar')],
                ['value' => 'cpev.value']
            )
            ->join(
                ['ea' => $connection->getTableName('eav_attribute')],
                'cpev.attribute_id = ea.attribute_id',
                ['attribute_code' => 'ea.attribute_code']
            )
            ->where('cpev.' . $linkField . ' = ?', $productId)
            ->where('cpev.store_id = ?', 0)
            ->where('ea.entity_type_id = ?', $entityTypeId)
            ->where('ea.attribute_code IN (?)', $this->imagesAttributes);

        $rows = $connection->fetchAll($select);

        $result = [];
        foreach ($rows as $row) {
            $code = (string) ($row['attribute_code'] ?? '');
            $val = (string) ($row['value'] ?? '');
            if ($code !== '') {
                $result[$code] = $val;
            }
        }

        return $result;
    }

    /**
     * @param string|null $value
     * @param int $storeId
     * @return string
     */
    private function normalizeImageValue(?string $value, int $storeId): string
    {
        $value = (string) $value;

        if ($value === '' || $value === 'no_selection') {
            return $this->getPlaceholder($storeId);
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
