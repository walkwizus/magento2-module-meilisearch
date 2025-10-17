<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\FragmentRenderer;

use Walkwizus\MeilisearchFrontend\Api\FragmentRendererInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\Pricing\Render as PriceRender;
use Magento\Catalog\Pricing\Price\FinalPrice;

class PriceFragmentRenderer implements FragmentRendererInterface
{
    /**
     * @var PriceRender|null
     */
    private ?PriceRender $block = null;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isProductSupported(ProductInterface $product): bool
    {
        return true;
    }

    /**
     * @param LayoutInterface $layout
     * @return void
     */
    public function prepare(LayoutInterface $layout): void
    {
        if ($this->block !== null) {
            return;
        }

        $this->block = $layout->createBlock(
            PriceRender::class,
            null,
            ['data' => ['price_render_handle' => 'catalog_product_prices']]
        );
    }

    /**
     * @param ProductInterface $product
     * @return string|null
     */
    public function render(ProductInterface $product): ?string
    {
        return $this->block->render(
            FinalPrice::PRICE_CODE,
            $product,
            [
                'display_minimal_price' => true,
                'use_link_for_as_low_as' => true,
                'zone' => PriceRender::ZONE_ITEM_LIST,
            ]
        );
    }
}
