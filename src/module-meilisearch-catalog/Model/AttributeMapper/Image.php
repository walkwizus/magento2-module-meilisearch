<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Image implements AttributeMapperInterface
{
    const XML_PATH_CATALOG_PLACEHOLDER_IMAGE_PLACEHOLDER = 'catalog/placeholder/image_placeholder';

    const IMAGE_ATTRIBUTE = 'image';

    const IMAGE_PATH_PLACEHOLDER = '/catalog/product/placeholder';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig
    ) { }

    /**
     * @param array $documentData
     * @param $storeId
     * @return array
     * @throws LocalizedException
     */
    public function map(array $documentData, $storeId): array
    {
        $documents = [];
        foreach ($documentData as $id => $indexData) {
            $documents[$id]['image'] = $this->getProductImage($id, $storeId);
        }

        return $documents;
    }

    /**
     * @param $productId
     * @param $storeId
     * @return string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductImage($productId, $storeId): string
    {
        $connection = $this->resourceConnection->getConnection();

        $entityTypeId = $this->eavConfig
            ->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();

        $select = $connection
            ->select()
            ->from(['main_table' => $connection->getTableName('catalog_product_entity_varchar')], ['main_table.value'])
            ->join(
                ['eav_attribute' => $connection->getTableName('eav_attribute')],
                'main_table.attribute_id = eav_attribute.attribute_id',
                []
            )
            ->where('main_table.entity_id = ?', $productId)
            ->where('main_table.store_id = ?', 0)
            ->where('eav_attribute.entity_type_id = ?', $entityTypeId)
            ->where('eav_attribute.attribute_code = "' . self::IMAGE_ATTRIBUTE . '"');

        $image = $connection->fetchOne($select);

        return !is_null($image) && $image != 'no_selection' && $image != '' ? $image : $this->getPlaceholder($storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    protected function getPlaceholder($storeId): string
    {
        $placeholder = $this->scopeConfig->getValue(self::XML_PATH_CATALOG_PLACEHOLDER_IMAGE_PLACEHOLDER, ScopeInterface::SCOPE_STORE, $storeId);

        if (!$placeholder) {
            $placeholder = 'image.jpg';
        }

        return self::IMAGE_PATH_PLACEHOLDER . '/' . $placeholder;
    }
}
