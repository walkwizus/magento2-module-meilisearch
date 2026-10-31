<?php

/**
 * Magento\Setup lives in the Magento root package (setup/src), which is not installable as a library.
 * This file is only scanned by PHPStan, it is never loaded at runtime.
 */

declare(strict_types=1);

namespace Magento\Setup\Model;

class SearchConfigOptionsList
{
    /**
     * @return \Magento\Framework\Setup\Option\AbstractConfigOption[]
     */
    public function getOptionsList(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableSearchEngineList(): array
    {
        return [];
    }
}
