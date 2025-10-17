<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

use Magento\Framework\View\LayoutFactory;
use Walkwizus\MeilisearchFrontend\Api\FragmentRendererInterface;

class FragmentAggregator
{
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
     * @param array $products
     * @return array
     */
    public function build(array $products): array
    {
        $layout = $this->layoutFactory->create();
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
}
