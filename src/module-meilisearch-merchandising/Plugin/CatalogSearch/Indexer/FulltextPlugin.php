<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Plugin\CatalogSearch\Indexer;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\RankSortState;

class FulltextPlugin
{
    /**
     * @param RankSortState $rankSortState
     */
    public function __construct(
        private readonly RankSortState $rankSortState
    ) { }

    /**
     * @param Fulltext $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecuteFull(Fulltext $subject, mixed $result): mixed
    {
        $this->rankSortState->markReady();

        return $result;
    }
}
