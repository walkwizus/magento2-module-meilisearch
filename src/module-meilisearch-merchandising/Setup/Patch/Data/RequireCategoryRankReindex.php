<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Setup\Patch\Data;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RequireCategoryRankReindex implements DataPatchInterface
{
    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * @return $this
     */
    public function apply(): static
    {
        $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();

        return $this;
    }

    /**
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
