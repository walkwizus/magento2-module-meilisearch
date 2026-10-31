<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider\ProductActionsConfigProvider;

class ProductActions implements ArgumentInterface
{
    /**
     * @var array|null
     */
    private ?array $config = null;

    /**
     * @param UrlInterface $urlBuilder
     * @param UrlHelper $urlHelper
     * @param PostHelper $postHelper
     * @param ProductActionsConfigProvider $configProvider
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly PostHelper $postHelper,
        private readonly ProductActionsConfigProvider $configProvider
    ) {
    }

    /**
     * @param array $hit
     * @return bool
     */
    public function isSaleable(array $hit): bool
    {
        return (bool)($hit['stock']['is_in_stock'] ?? false);
    }

    /**
     * @param array $hit
     * @return array
     */
    public function getAddToCartPostParams(array $hit): array
    {
        $productId = (int)$hit['id'];
        $url = $this->urlBuilder->getUrl('checkout/cart/add', [
            ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl(),
            'product' => $productId,
        ]);

        return [
            'action' => $url,
            'data' => [
                'product' => $productId,
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            ],
        ];
    }

    /**
     * @return bool
     */
    public function isWishlistEnabled(): bool
    {
        return (bool)$this->getConfig()['wishlistEnabled'];
    }

    /**
     * @param array $hit
     * @return string
     */
    public function getAddToWishlistPostData(array $hit): string
    {
        return $this->postHelper->getPostData(
            $this->getConfig()['addToWishlistUrl'],
            ['product' => (int)$hit['id']]
        );
    }

    /**
     * @param array $hit
     * @return string
     */
    public function getAddToComparePostData(array $hit): string
    {
        return $this->postHelper->getPostData(
            $this->getConfig()['addToCompareUrl'],
            ['product' => (int)$hit['id']]
        );
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = $this->configProvider->get()['productActions'];
        }

        return $this->config;
    }
}
