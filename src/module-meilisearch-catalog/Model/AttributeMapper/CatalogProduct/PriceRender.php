<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchCatalog\Model\AttributeMapper\CatalogProduct;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Group;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Walkwizus\MeilisearchBase\Api\AttributeMapperInterface;
use Walkwizus\MeilisearchCatalog\Model\Price\StoreCurrencyResolver;
use Walkwizus\MeilisearchCatalog\Model\Tax\ProductPriceCalculator;

class PriceRender implements AttributeMapperInterface
{
    private const DEFAULT_PRICE_ATTRIBUTE = 'price_0';

    private const BUNDLE_TYPE = 'bundle';

    private const PRICE_VIEW_ATTRIBUTE = 'price_view';

    private const TIER_PRICE_ATTRIBUTE = 'tier_price';

    private const REGULAR_PRICE_FIELD = 'price';

    private const PRICE_FIELDS = [
        'price',
        'final_price',
        'min_price',
        'max_price',
        'tier_price',
    ];

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductPriceCalculator $priceCalculator
     * @param MetadataPool $metadataPool
     * @param EavConfig $eavConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreCurrencyResolver $storeCurrencyResolver
     * @param ProductFactory $productFactory
     * @param GroupManagementInterface $groupManagement
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProductPriceCalculator $priceCalculator,
        private readonly MetadataPool $metadataPool,
        private readonly EavConfig $eavConfig,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly StoreCurrencyResolver $storeCurrencyResolver,
        private readonly ProductFactory $productFactory,
        private readonly GroupManagementInterface $groupManagement
    ) {
    }

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
        $productIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        array_keys($documentData)
                    )
                )
            )
        );

        if ($productIds === []) {
            return [];
        }

        $storeId = (int) $storeId;
        $store = $this->storeManager->getStore($storeId);
        $websiteId = (int) $store->getWebsiteId();

        $priceRows = $this->fetchPriceRows(
            $productIds,
            $websiteId
        );

        $customerTaxClasses = $this->getCustomerTaxClassesByGroup(
            $storeId
        );

        $productTypes = $this->fetchProductTypes(
            $productIds
        );

        $tierPriceProducts = $this->fetchTierPriceProducts(
            $productIds
        );

        $currencyCodes = $this->storeCurrencyResolver->getCurrencyCodes(
            $storeId
        );

        $allGroupsId = (int) $this->groupManagement->getAllCustomersGroup()->getId();
        $tierPrices = [];

        $result = [];
        $defaultRows = [];

        foreach ($productIds as $productId) {
            $result[$productId] = [
                'price_render' => [],
            ];

            if (isset($productTypes[$productId])) {
                $result[$productId]['type_id'] = $productTypes[$productId]['type_id'];

                if ($productTypes[$productId]['type_id'] === self::BUNDLE_TYPE) {
                    $result[$productId][self::PRICE_VIEW_ATTRIBUTE] = $productTypes[$productId]['price_view'];
                }
            }
        }

        foreach ($priceRows as $row) {
            $productId = (int) ($row['entity_id'] ?? 0);
            $customerGroupId = (int) ($row['customer_group_id'] ?? 0);
            $row['customer_tax_class_id'] = $customerTaxClasses[$customerGroupId]
                ?? $customerTaxClasses[Group::NOT_LOGGED_IN_ID];

            if (isset($tierPriceProducts[$productId]) && !isset($tierPrices[$productId])) {
                $tierPrices[$productId] = $this->loadTierPrices(
                    $productId,
                    $tierPriceProducts[$productId],
                    $productTypes[$productId]['type_id'] ?? '',
                    $row,
                    $storeId
                );
            }

            $row['tier_price'] = $this->getMinimalTierPrice(
                $tierPrices[$productId] ?? [],
                $row,
                $allGroupsId
            );

            if ($productId === 0 || $this->isDefaultGroupDuplicate($row, $defaultRows)) {
                continue;
            }

            $hasDiscount = isset($row['price'])
                && (
                    (isset($row['final_price']) && (float) $row['final_price'] < (float) $row['price'])
                    || (isset($row['min_price']) && (float) $row['min_price'] < (float) $row['price'])
                );
            $isRange = isset($row['min_price'], $row['max_price'])
                && (float) $row['min_price'] !== (float) $row['max_price'];

            foreach ($currencyCodes as $currencyCode) {
                $prices = $this->buildPrices($row, $storeId, $currencyCode);

                $result[$productId]['price_render']['price_' . $customerGroupId][$currencyCode] = $this->removeNullValues(
                    $prices + [
                        'has_discount' => $hasDiscount,
                        'is_range' => $isRange,
                    ]
                );
            }
        }

        $this->priceCalculator->reset();

        return $result;
    }

    /**
     * @param array $row
     * @param array $defaultRows
     * @return bool
     */
    private function isDefaultGroupDuplicate(array $row, array &$defaultRows): bool
    {
        $productId = (int) $row['entity_id'];
        $priceAttribute = 'price_' . (int) $row['customer_group_id'];
        unset($row['customer_group_id']);

        if ($priceAttribute === self::DEFAULT_PRICE_ATTRIBUTE) {
            $defaultRows[$productId] = $row;

            return false;
        }

        return isset($defaultRows[$productId]) && $defaultRows[$productId] === $row;
    }

    /**
     * @param array $prices
     * @return array
     */
    private function removeNullValues(array $prices): array
    {
        foreach ($prices as $field => $value) {
            if ($value === null) {
                unset($prices[$field]);
            }
        }

        return $prices;
    }

    /**
     * @param array $productIds
     * @param int $websiteId
     * @return array
     */
    private function fetchPriceRows(array $productIds, int $websiteId): array
    {
        $connection = $this->resource->getConnection();

        $priceIndexTable = $this->resource->getTableName('catalog_product_index_price');

        $select = $connection->select()
            ->from($priceIndexTable,
                [
                    'entity_id',
                    'customer_group_id',
                    'website_id',
                    'tax_class_id',
                    'price',
                    'final_price',
                    'min_price',
                    'max_price',
                ]
            )
            ->where('entity_id IN (?)', $productIds)
            ->where('website_id = ?', $websiteId)
            ->order(['entity_id ASC', 'customer_group_id ASC']);

        return $connection->fetchAll($select);
    }

    /**
     * @param array $productIds
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    private function fetchProductTypes(array $productIds): array
    {
        $connection = $this->resource->getConnection();
        $linkField = $this->getLinkField();

        $select = $connection->select()
            ->from(['product' => $this->resource->getTableName('catalog_product_entity')], ['entity_id', 'type_id'])
            ->where('product.entity_id IN (?)', $productIds);

        $priceViewAttributeId = (int) $this->eavConfig
            ->getAttribute(ProductAttributeInterface::ENTITY_TYPE_CODE, self::PRICE_VIEW_ATTRIBUTE)
            ->getId();

        if ($priceViewAttributeId > 0) {
            $select->joinLeft(
                ['price_view' => $this->resource->getTableName('catalog_product_entity_int')],
                'price_view.' . $linkField . ' = product.' . $linkField
                . $connection->quoteInto(' AND price_view.attribute_id = ?', $priceViewAttributeId)
                . ' AND price_view.store_id = 0',
                ['price_view' => 'value']
            );
        }

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int) $row['entity_id']] = [
                'type_id' => (string) $row['type_id'],
                'price_view' => (int) ($row['price_view'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @param array $productIds
     * @return array
     * @throws \Exception
     */
    private function fetchTierPriceProducts(array $productIds): array
    {
        $connection = $this->resource->getConnection();
        $linkField = $this->getLinkField();

        $select = $connection->select()
            ->distinct()
            ->from(['product' => $this->resource->getTableName('catalog_product_entity')], ['entity_id', $linkField])
            ->join(
                ['tier' => $this->resource->getTableName('catalog_product_entity_tier_price')],
                'tier.' . $linkField . ' = product.' . $linkField,
                []
            )
            ->where('product.entity_id IN (?)', $productIds)
            ->where('tier.qty > 1');

        return array_map('intval', $connection->fetchPairs($select));
    }

    /**
     * @param int $productId
     * @param int $linkId
     * @param string $typeId
     * @param array $row
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    private function loadTierPrices(int $productId, int $linkId, string $typeId, array $row, int $storeId): array
    {
        $product = $this->productFactory->create();
        $product->setId($productId);
        $product->setData($this->getLinkField(), $linkId);
        $product->setTypeId($typeId);
        $product->setStoreId($storeId);
        $product->setPrice($row['price'] ?? null);

        $this->eavConfig
            ->getAttribute(ProductAttributeInterface::ENTITY_TYPE_CODE, self::TIER_PRICE_ATTRIBUTE)
            ->getBackend()
            ->afterLoad($product);

        return (array) $product->getData(self::TIER_PRICE_ATTRIBUTE);
    }

    /**
     * @param array $tierPrices
     * @param array $row
     * @param int $allGroupsId
     * @return float|null
     */
    private function getMinimalTierPrice(array $tierPrices, array $row, int $allGroupsId): ?float
    {
        $customerGroupId = (int) $row['customer_group_id'];
        $minimalPrice = null;

        foreach ($tierPrices as $tierPrice) {
            $tierGroupId = (int) ($tierPrice['cust_group'] ?? -1);

            if ((float) ($tierPrice['price_qty'] ?? 1) == 1.0
                || ($tierGroupId !== $customerGroupId && $tierGroupId !== $allGroupsId)
            ) {
                continue;
            }

            $price = (float) $tierPrice['price'];
            $minimalPrice = $minimalPrice === null ? $price : min($minimalPrice, $price);
        }

        if ($minimalPrice === null || !isset($row['final_price']) || $minimalPrice >= (float) $row['final_price']) {
            return null;
        }

        return $minimalPrice;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function getCustomerTaxClassesByGroup(int $storeId): array
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from($this->resource->getTableName('customer_group'), ['customer_group_id', 'tax_class_id']);

        $taxClasses = array_map('intval', $connection->fetchPairs($select));

        $taxClasses[Group::NOT_LOGGED_IN_ID] = (int) $this->scopeConfig->getValue(
            TaxHelper::CONFIG_DEFAULT_CUSTOMER_TAX_CLASS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $taxClasses;
    }

    /**
     * @param array $row
     * @param int $storeId
     * @param string $currencyCode
     * @return array
     */
    private function buildPrices(array $row, int $storeId, string $currencyCode): array
    {
        $result = [];

        foreach (self::PRICE_FIELDS as $field) {
            $result[$field] = isset($row[$field])
                ? $this->priceCalculator->calculate(
                    $this->convertPrice((float) $row[$field], $row, $field, $storeId, $currencyCode),
                    (int) ($row['tax_class_id'] ?? 0),
                    (int) $row['customer_tax_class_id'],
                    $storeId
                )
                : null;
        }

        return $result;
    }

    /**
     * @param float $amount
     * @param array $row
     * @param string $field
     * @param int $storeId
     * @param string $currencyCode
     * @return float
     */
    private function convertPrice(float $amount, array $row, string $field, int $storeId, string $currencyCode): float
    {
        $isRegularPrice = $field === self::REGULAR_PRICE_FIELD
            || ($field !== self::TIER_PRICE_ATTRIBUTE
                && isset($row[self::REGULAR_PRICE_FIELD])
                && $amount === (float) $row[self::REGULAR_PRICE_FIELD]);

        if ($isRegularPrice) {
            return (float) $this->priceCurrency->convert($amount, $storeId, $currencyCode);
        }

        return (float) $this->priceCurrency->convertAndRound($amount, $storeId, $currencyCode);
    }
}
