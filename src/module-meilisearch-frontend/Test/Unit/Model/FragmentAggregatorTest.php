<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchFrontend\Api\FragmentRendererInterface;
use Walkwizus\MeilisearchFrontend\Model\FragmentAggregator;

class FragmentAggregatorTest extends TestCase
{
    public function testGetFragmentsCodeCanFilterDisabledRenderers(): void
    {
        $enabledRenderer = $this->createMock(FragmentRendererInterface::class);
        $enabledRenderer->method('isEnabled')->willReturn(true);

        $disabledRenderer = $this->createMock(FragmentRendererInterface::class);
        $disabledRenderer->method('isEnabled')->willReturn(false);

        $layoutFactory = $this->createMock(LayoutFactory::class);

        $aggregator = new FragmentAggregator(
            [
                'price' => $enabledRenderer,
                'swatch' => $disabledRenderer,
            ],
            $layoutFactory
        );

        self::assertSame(['price'], $aggregator->getFragmentsCode(true));
        self::assertSame(['price', 'swatch'], $aggregator->getFragmentsCode(false));
    }

    public function testBuildRendersFragmentsForSupportedProductsOnly(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layoutFactory = $this->createMock(LayoutFactory::class);
        $layoutFactory->expects(self::once())
            ->method('create')
            ->willReturn($layout);

        $enabledRenderer = $this->createMock(FragmentRendererInterface::class);
        $enabledRenderer->method('isEnabled')->willReturn(true);
        $enabledRenderer->expects(self::once())
            ->method('prepare')
            ->with($layout);

        $disabledRenderer = $this->createMock(FragmentRendererInterface::class);
        $disabledRenderer->method('isEnabled')->willReturn(false);
        $disabledRenderer->expects(self::never())
            ->method('prepare');
        $disabledRenderer->expects(self::never())
            ->method('render');

        $product1 = $this->createMock(ProductInterface::class);
        $product1->method('getSku')->willReturn('sku-1');

        $product2 = $this->createMock(ProductInterface::class);
        $product2->method('getSku')->willReturn('sku-2');

        $enabledRenderer->method('isProductSupported')->willReturnMap([
            [$product1, true],
            [$product2, false],
        ]);
        $enabledRenderer->expects(self::once())
            ->method('render')
            ->with($product1)
            ->willReturn('<div>price fragment</div>');

        $aggregator = new FragmentAggregator(
            [
                'price' => $enabledRenderer,
                'swatch' => $disabledRenderer,
            ],
            $layoutFactory
        );

        self::assertSame(
            [
                'price' => [
                    'sku-1' => '<div>price fragment</div>',
                ],
            ],
            $aggregator->build([$product1, $product2])
        );
    }
}
