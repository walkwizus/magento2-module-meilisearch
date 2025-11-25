<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Walkwizus\MeilisearchFrontend\Api\FragmentRendererInterface;

class FragmentAggregator
{
    /**
     * @var LayoutInterface|null
     */
    private ?LayoutInterface $layout = null;

    /**
     * @param array $renderers
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        private readonly array $renderers,
        private readonly LayoutFactory $layoutFactory
    ) { }

    /**
     * @param bool $enabled
     * @return array
     */
    public function getFragmentsCode(bool $enabled = true): array
    {
        if ($enabled) {
            $codes = [];
            foreach ($this->renderers as $code => $renderer) {
                if ($renderer->isEnabled()) {
                    $codes[] = $code;
                }
            }

            return $codes;
        }

        return array_keys($this->renderers);
    }

    /**
     * @param $products
     * @return array
     */
    public function build($products): array
    {
        $layout = $this->getLayout();
        $output = [];

        /** @var FragmentRendererInterface $renderer */
        foreach ($this->renderers as $renderer) {
            if ($renderer->isEnabled()) {
                $renderer->prepare($layout);
            }
        }

        /** @var FragmentRendererInterface $renderer */
        foreach ($this->renderers as $code => $renderer) {
            if (!$renderer->isEnabled()) {
                continue;
            }

            foreach ($products as $product) {
                if (!$renderer->isProductSupported($product)) {
                    continue;
                }

                $html = $renderer->render($product);

                if ($html) {
                    $output[$code][$product->getSku()] = $html;
                }
            }
        }

        return $output;
    }

    /**
     * @return LayoutInterface
     */
    private function getLayout(): LayoutInterface
    {
        if ($this->layout === null) {
            $this->layout = $this->layoutFactory->create();
        }

        return $this->layout;
    }
}
