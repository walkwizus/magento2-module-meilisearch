<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper;

use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Magento\AdvancedSearch\Model\ResourceModel\Index;

class Price implements AttributeMapperInterface
{
    /**
     * @param Index $index
     */
    public function __construct(
        private readonly Index $index
    ) { }

    /***
     * @param array $documentData
     * @param $storeId
     * @return array
     */
    public function map(array $documentData, $storeId): array
    {
        $productIds = array_keys($documentData);
        $priceIndexData = $this->index->getPriceIndexData($productIds, $storeId);
        $data = [];

        foreach ($productIds as $productId) {
            $data[$productId] = $this->getProductPriceData($productId, $priceIndexData);
        }

        return $data;
    }

    /**
     * @param $productId
     * @param array $priceIndexData
     * @return array
     */
    protected function getProductPriceData($productId, array $priceIndexData): array
    {
        $result = [];
        if (array_key_exists($productId, $priceIndexData)) {
            $productPriceIndexData = $priceIndexData[$productId];
            foreach ($productPriceIndexData as $customerGroupId => $price) {
                $result['price_' . $customerGroupId] = (float)sprintf('%F', $price);
            }
        }

        return $result;
    }
}
