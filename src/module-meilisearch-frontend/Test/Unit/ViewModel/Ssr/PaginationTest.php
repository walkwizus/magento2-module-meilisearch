<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Test\Unit\ViewModel\Ssr;

use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;
use Walkwizus\MeilisearchFrontend\ViewModel\Ssr\Pagination;

class PaginationTest extends TestCase
{
    public function testFrameAroundTheCurrentPage(): void
    {
        self::assertSame([3, 4, 5, 6, 7], $this->pagination()->getFramePages(['page' => 5, 'totalPages' => 10]));
    }

    public function testPagePastTheLastIsTreatedAsTheLast(): void
    {
        $pagination = $this->pagination();

        self::assertSame([1, 2], $pagination->getFramePages(['page' => 1000, 'totalPages' => 2]));
        self::assertSame([1, 2], $pagination->getFramePages(['page' => PHP_INT_MAX, 'totalPages' => 2]));
        self::assertSame(2, $pagination->getCurrentPage(['page' => 1000, 'totalPages' => 2]));
    }

    private function pagination(): Pagination
    {
        return new Pagination($this->createMock(UrlInterface::class));
    }
}
