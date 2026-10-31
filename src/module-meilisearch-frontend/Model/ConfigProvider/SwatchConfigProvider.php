<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Magento\Eav\Model\Cache\Type as EavCache;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Cache\StateInterface as CacheState;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Helper\Media as SwatchMedia;
use Magento\Swatches\Model\Swatch;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;
use Zend_Db_Expr;

class SwatchConfigProvider implements ConfigProviderInterface
{
    private const CACHE_KEY_PREFIX = 'meilisearch_swatch_options_';

    private const LISTING_SWATCH_IMAGE_TYPE = 'swatch_image';

    private const LISTING_SWATCH_INPUT_TYPES = [
        Swatch::SWATCH_INPUT_TYPE_VISUAL,
        Swatch::SWATCH_INPUT_TYPE_TEXT,
    ];

    /**
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param StoreFront $storeFront
     * @param SwatchMedia $swatchMedia
     * @param CacheInterface $cache
     * @param CacheState $cacheState
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreFront $storeFront,
        private readonly SwatchMedia $swatchMedia,
        private readonly CacheInterface $cache,
        private readonly CacheState $cacheState,
        private readonly SerializerInterface $serializer
    ) { }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        if (!$this->storeFront->getShowSwatchesInProductList($storeId)) {
            return ['swatchOptions' => []];
        }

        return ['swatchOptions' => $this->getCachedSwatchOptions($storeId)];
    }

    /**
     * @param int $storeId
     * @return array
     */
    private function getCachedSwatchOptions(int $storeId): array
    {
        if (!$this->cacheState->isEnabled(EavCache::TYPE_IDENTIFIER)) {
            return $this->getSwatchOptions($this->getListingSwatchAttributeIds(), $storeId);
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $storeId;
        $cached = $this->cache->load($cacheKey);

        if ($cached !== false) {
            return $this->serializer->unserialize($cached);
        }

        $swatchOptions = $this->getSwatchOptions($this->getListingSwatchAttributeIds(), $storeId);
        $this->cache->save(
            $this->serializer->serialize($swatchOptions),
            $cacheKey,
            [Attribute::CACHE_TAG, EavCache::CACHE_TAG]
        );

        return $swatchOptions;
    }

    /**
     * @return array
     */
    private function getListingSwatchAttributeIds(): array
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_eav_attribute'), ['attribute_id', 'additional_data'])
            ->where('used_in_product_listing = ?', 1);

        $attributeIds = [];
        foreach ($connection->fetchPairs($select) as $attributeId => $additionalData) {
            $additionalData = json_decode((string) $additionalData, true);
            $inputType = is_array($additionalData) ? ($additionalData[Swatch::SWATCH_INPUT_TYPE_KEY] ?? null) : null;

            if (in_array($inputType, self::LISTING_SWATCH_INPUT_TYPES, true)) {
                $attributeIds[] = (int) $attributeId;
            }
        }

        return $attributeIds;
    }

    /**
     * @param array $attributeIds
     * @param int $storeId
     * @return array
     */
    private function getSwatchOptions(array $attributeIds, int $storeId): array
    {
        if ($attributeIds === []) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $optionValueTable = $this->resource->getTableName('eav_attribute_option_value');
        $swatchTable = $this->resource->getTableName('eav_attribute_option_swatch');

        $select = $connection->select()
            ->from(['eao' => $this->resource->getTableName('eav_attribute_option')], ['attribute_id', 'option_id', 'sort_order'])
            ->joinLeft(
                ['value_store' => $optionValueTable],
                'value_store.option_id = eao.option_id' . $connection->quoteInto(' AND value_store.store_id = ?', $storeId),
                []
            )
            ->joinLeft(
                ['value_default' => $optionValueTable],
                'value_default.option_id = eao.option_id AND value_default.store_id = 0',
                []
            )
            ->joinLeft(
                ['swatch_store' => $swatchTable],
                'swatch_store.option_id = eao.option_id' . $connection->quoteInto(' AND swatch_store.store_id = ?', $storeId),
                []
            )
            ->joinLeft(
                ['swatch_default' => $swatchTable],
                'swatch_default.option_id = eao.option_id AND swatch_default.store_id = 0',
                []
            )
            ->columns([
                'label' => new Zend_Db_Expr('COALESCE(value_store.value, value_default.value)'),
                'swatch_type' => new Zend_Db_Expr('COALESCE(swatch_store.type, swatch_default.type)'),
                'swatch_value' => new Zend_Db_Expr('COALESCE(swatch_store.value, swatch_default.value)'),
            ])
            ->where('eao.attribute_id IN (?)', $attributeIds);

        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $swatchType = $row['swatch_type'] !== null ? (int) $row['swatch_type'] : null;

            $result[(int) $row['attribute_id']][(int) $row['option_id']] = [
                'label' => (string) ($row['label'] ?? ''),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'swatch_type' => $swatchType,
                'swatch_value' => $row['swatch_value'],
                'swatch_image' => $swatchType === Swatch::SWATCH_TYPE_VISUAL_IMAGE
                    ? $this->getSwatchImagePath((string) $row['swatch_value'])
                    : '',
            ];
        }

        return $result;
    }

    /**
     * @param string $file
     * @return string
     */
    private function getSwatchImagePath(string $file): string
    {
        if ($file === '') {
            return '';
        }

        $url = $this->swatchMedia->getSwatchAttributeImage(self::LISTING_SWATCH_IMAGE_TYPE, $file);
        $swatchMediaUrl = $this->swatchMedia->getSwatchMediaUrl();

        if ($url === '' || !str_starts_with($url, $swatchMediaUrl)) {
            return '';
        }

        return $this->swatchMedia->getSwatchMediaPath() . substr($url, strlen($swatchMediaUrl));
    }
}
