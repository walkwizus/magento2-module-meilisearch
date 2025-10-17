<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\LayoutInterface;

interface FragmentRendererInterface
{
    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isProductSupported(ProductInterface $product): bool;

    /**
     * @param LayoutInterface $layout
     * @return void
     */
    public function prepare(LayoutInterface $layout): void;

    /**
     * @param ProductInterface $product
     * @return string|null
     */
    public function render(ProductInterface $product): ?string;
}
