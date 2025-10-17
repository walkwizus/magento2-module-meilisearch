<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\FragmentRenderer;

use Magento\Swatches\Block\Product\Renderer\Listing\Configurable;
use Walkwizus\MeilisearchFrontend\Api\FragmentRendererInterface;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;
use Magento\Swatches\ViewModel\Product\Renderer\Configurable as ConfigurableViewModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\LayoutInterface;

class SwatchFragmentRenderer implements FragmentRendererInterface
{
    /**
     * @var Configurable|null
     */
    private ?Configurable $block = null;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StoreFront $storeFront
     * @param ConfigurableViewModel $configurableViewModel
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreFront $storeFront,
        private readonly ConfigurableViewModel $configurableViewModel
    ) { }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isEnabled(): bool
    {
        return (bool)$this->storeFront->getShowSwatchesInProductList($this->storeManager->getStore()->getId());
    }

    /**
     * @param ProductInterface $product
     * @return bool
     */
    public function isProductSupported(ProductInterface $product): bool
    {
        return $product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
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
            Configurable::class,
            null,
            ['data' => ['configurable_view_model' => $this->configurableViewModel]]
        )->setTemplate('Magento_Swatches::product/listing/renderer.phtml');
    }

    /**
     * @param ProductInterface $product
     * @return string|null
     */
    public function render(ProductInterface $product): ?string
    {
        $this->block->setProduct($product);

        return $this->block->toHtml() ?: '';
    }
}
