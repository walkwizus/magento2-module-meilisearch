<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\ViewModel\Ssr;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Meilisearch\Search\SearchResult;

class Pagination implements ArgumentInterface
{
    /**
     * @param int $frameLength
     */
    public function __construct(
        private readonly int $frameLength = 5
    ) { }

    /**
     * @param SearchResult $result
     * @return bool
     */
    public function hasPagination(SearchResult $result): bool
    {
        return $this->getLastPageNum($result) > 1;
    }

    /**
     * @param SearchResult $result
     * @return int
     */
    public function getCurrentPage(SearchResult $result): int
    {
        return $result->getPage() ?? 1;
    }

    /**
     * @param SearchResult $result
     * @return int
     */
    public function getLastPageNum(SearchResult $result): int
    {
        return $result->getTotalPages() ?? 1;
    }

    /**
     * @param SearchResult $result
     * @return array
     */
    public function getFramePages(SearchResult $result): array
    {
        $currentPage = $this->getCurrentPage($result);
        $lastPageNum = $this->getLastPageNum($result);

        $half = (int) floor(($this->frameLength - 1) / 2);
        $start = max(1, $currentPage - $half);
        $end = min($lastPageNum, $start + $this->frameLength - 1);

        if ($end - $start + 1 < $this->frameLength && $lastPageNum >= $this->frameLength) {
            $start = max(1, $lastPageNum - $this->frameLength + 1);
        }

        return range($start, $end);
    }

    /**
     * @param SearchResult $result
     * @return bool
     */
    public function isFirstPage(SearchResult $result): bool
    {
        return $this->getCurrentPage($result) <= 1;
    }

    /**
     * @param SearchResult $result
     * @return bool
     */
    public function isLastPage(SearchResult $result): bool
    {
        return $this->getCurrentPage($result) >= $this->getLastPageNum($result);
    }

    /**
     * @param array $framePages
     * @return bool
     */
    public function canShowFirst(array $framePages): bool
    {
        return (int)reset($framePages) > 1;
    }

    /**
     * @param array $framePages
     * @return bool
     */
    public function canShowPreviousJump(array $framePages): bool
    {
        return (int)reset($framePages) > 2;
    }

    /**
     * @param array $framePages
     * @param int $lastPageNum
     * @return bool
     */
    public function canShowNextJump(array $framePages, int $lastPageNum): bool
    {
        return (int)end($framePages) < $lastPageNum - 1;
    }

    /**
     * @param array $framePages
     * @param int $lastPageNum
     * @return bool
     */
    public function canShowLast(array $framePages, int $lastPageNum): bool
    {
        return (int)end($framePages) < $lastPageNum;
    }

    /**
     * @param array $framePages
     * @return int
     */
    public function getPreviousJumpPage(array $framePages): int
    {
        $start = (int)reset($framePages);
        return max(1, $start - 1);
    }

    /**
     * @param array $framePages
     * @param int $lastPageNum
     * @return int
     */
    public function getNextJumpPage(array $framePages, int $lastPageNum): int
    {
        $end = (int)end($framePages);
        return min($lastPageNum, $end + 1);
    }

    /**
     * @param string $baseUrl
     * @param int $page
     * @return string
     */
    public function getPageUrl(string $baseUrl, int $page): string
    {
        return $page <= 1 ? $baseUrl : $baseUrl . '?page=' . $page;
    }
}
