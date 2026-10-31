<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Test\Unit\ViewModel;

use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Meilisearch\Contracts\HybridSearchOptions;
use Meilisearch\Contracts\MultiSearchQuery;
use Meilisearch\Contracts\MultiSearchQueryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchFrontend\Model\ConfigProvider;
use Walkwizus\MeilisearchFrontend\ViewModel\Ssr;

class SsrRequestParamsTest extends TestCase
{
    public function testValidParamsAreUsed(): void
    {
        $ssr = $this->createSsr([
            'product_list_mode' => 'list',
            'product_list_limit' => '20',
            'product_list_order' => 'price',
            'product_list_dir' => 'DESC',
        ]);

        self::assertSame('list', $ssr->getProductListMode());
        self::assertSame(20, $ssr->getHitsPerPage());
        self::assertSame('price', $ssr->getSortBy());
        self::assertTrue($ssr->isDescending());
    }

    public function testUnknownListModeFallsBackToDefault(): void
    {
        $ssr = $this->createSsr(['product_list_mode' => 'foo']);

        self::assertSame('grid', $ssr->getProductListMode());
        self::assertSame(12, $ssr->getHitsPerPage());
    }

    public function testListModeTheStoreDoesNotOfferFallsBackToDefault(): void
    {
        $ssr = $this->createSsr(['product_list_mode' => 'list'], ['grid' => 'Grid']);

        self::assertSame('grid', $ssr->getProductListMode());
    }

    public function testLimitOutsideTheConfiguredValuesFallsBackToDefault(): void
    {
        self::assertSame(12, $this->createSsr(['product_list_limit' => '1000'])->getHitsPerPage());
        self::assertSame(12, $this->createSsr(['product_list_limit' => '1e3'])->getHitsPerPage());
        self::assertSame(12, $this->createSsr(['product_list_limit' => '2.4e1'])->getHitsPerPage());
        // phpcs:ignore PHPCompatibility.Numbers.RemovedHexadecimalNumericStrings.Found -- deliberately invalid input
        self::assertSame(12, $this->createSsr(['product_list_limit' => '0x18'])->getHitsPerPage());
        self::assertSame(36, $this->createSsr(['product_list_limit' => '36'])->getHitsPerPage());
    }

    public function testArrayParamsAreIgnored(): void
    {
        $ssr = $this->createSsr([
            'product_list_mode' => ['x'],
            'product_list_limit' => ['x'],
            'product_list_order' => ['x'],
            'product_list_dir' => ['x'],
        ]);

        self::assertSame('grid', $ssr->getProductListMode());
        self::assertSame(12, $ssr->getHitsPerPage());
        self::assertSame('position', $ssr->getSortBy());
        self::assertFalse($ssr->isDescending());
    }

    public function testPagePastTheLastShowsTheLastPage(): void
    {
        $pages = [];
        $searchManager = $this->createMock(SearchManager::class);
        $searchManager->method('multisearch')->willReturnCallback(function (array $queries) use (&$pages) {
            $pages[] = $queries[0]->toArray()['page'];
            return ['results' => [['hits' => [], 'totalPages' => 2, 'page' => end($pages)]]];
        });

        $result = $this->createSsr(['page' => '5'], searchManager: $searchManager)->getSearchResult();

        self::assertSame([5, 2], $pages);
        self::assertSame(2, $result['page']);
    }

    public function testPageInRangeIsSearchedOnce(): void
    {
        $searchManager = $this->createMock(SearchManager::class);
        $searchManager->expects(self::once())->method('multisearch')
            ->willReturn(['results' => [['hits' => [], 'totalPages' => 2, 'page' => 2]]]);

        $this->createSsr(['page' => '2'], searchManager: $searchManager)->getSearchResult();
    }

    private function createSsr(
        array $params,
        array $viewModes = ['grid' => 'Grid', 'list' => 'List'],
        ?SearchManager $searchManager = null
    ): Ssr {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->method('get')->willReturn([
            'defaultViewMode' => 'grid',
            'availableViewMode' => $viewModes,
            'gridPerPage' => 12,
            'gridPerPageValues' => [12, 24, 36],
            'listPerPage' => 10,
            'listPerPageValues' => [5, 10, 15, 20, 25],
            'defaultSortBy' => 'position',
            'availableSortBy' => ['position' => 'Position', 'price' => 'Price'],
            'indexName' => 'catalog_product',
        ]);

        $queryFactory = $this->createMock(MultiSearchQueryFactory::class);
        $queryFactory->method('create')->willReturnCallback(fn () => new MultiSearchQuery());

        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            fn (string $name, $default = null) => $params[$name] ?? $default
        );
        $request->method('getParams')->willReturn($params);

        return new Ssr(
            $request,
            $queryFactory,
            $this->createMock(HybridSearchOptions::class),
            $searchManager ?? $this->createMock(SearchManager::class),
            $configProvider,
            $this->createMock(PriceCurrencyInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ImageUrlBuilder::class)
        );
    }
}
