<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;
use Walkwizus\MeilisearchFrontend\Model\FragmentAggregator;
use Magento\Framework\View\ConfigInterface as ViewConfig;
use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;

class CatalogStoreFrontConfigProvider implements ConfigProviderInterface
{
    /**
     * Sentinel file path used to derive the image URL prefix/suffix from UrlBuilder.
     */
    private const IMAGE_PATH_TOKEN = '/meilisearch-image-path-token.jpg';

    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreFront $storeFront
     * @param FragmentAggregator $fragmentAggregator
     * @param ViewConfig $viewConfig
     * @param ImageUrlBuilder $imageUrlBuilder
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreFront $storeFront,
        private readonly FragmentAggregator $fragmentAggregator,
        private readonly ViewConfig $viewConfig,
        private readonly ImageUrlBuilder $imageUrlBuilder
    ) { }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(): array
    {
        $storeId = $this->storeManager->getStore()->getId();

        $config = $this->viewConfig->getViewConfig()->getMediaEntities('Magento_Catalog', 'images');
        $images = [
            'category_page_grid' => $config['category_page_grid'],
            'category_page_list' => $config['category_page_list'],
            'mini_cart_product_thumbnail' => $config['mini_cart_product_thumbnail'],
        ];

        foreach ($images as $imageId => &$cfg) {
            [$cfg['urlPrefix'], $cfg['urlSuffix']] = $this->getImageUrlParts((string)$imageId);
        }
        unset($cfg);

        return [
            'listMode' => $this->storeFront->getListMode($storeId),
            'gridPerPageValues' => array_map('intval', explode(',', $this->storeFront->getGridPerPageValues($storeId))),
            'gridPerPage' => (int)$this->storeFront->getGridPerPage($storeId),
            'listPerPageValues' => array_map('intval', explode(',', $this->storeFront->getListPerPageValues($storeId))),
            'listPerPage' => (int)$this->storeFront->getListPerPage($storeId),
            'listAllowAll' => (bool)$this->storeFront->getListAllowAll($storeId),
            'showSwatchesInProductList' => (bool)$this->storeFront->getShowSwatchesInProductList($storeId),
            'fragments' => $this->fragmentAggregator->getFragmentsCode(),
            'images' => $images,
        ];
    }

    /**
     * Split a UrlBuilder-generated URL around the image path, so the storefront JS can rebuild it
     * for any image without re-deriving the resize hash or the transformation query string itself.
     *
     * @param string $imageId
     * @return array{0: string|null, 1: string|null}
     */
    private function getImageUrlParts(string $imageId): array
    {
        $url = $this->imageUrlBuilder->getUrl(self::IMAGE_PATH_TOKEN, $imageId);
        $parts = explode(ltrim(self::IMAGE_PATH_TOKEN, '/'), $url, 2);

        return count($parts) === 2 ? $parts : [null, null];
    }
}
