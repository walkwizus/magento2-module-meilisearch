<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model\CategoryProduct;

use Magento\Framework\FlagManager;

class RankSortState
{
    private const FLAG_CODE = 'meilisearch_merchandising_rank_sort_ready';

    /**
     * @param FlagManager $flagManager
     */
    public function __construct(
        private readonly FlagManager $flagManager
    ) {
    }

    /**
     * @return bool
     */
    public function isReady(): bool
    {
        return (bool) $this->flagManager->getFlagData(self::FLAG_CODE);
    }

    /**
     * @return void
     */
    public function markReady(): void
    {
        $this->flagManager->saveFlag(self::FLAG_CODE, true);
    }
}
