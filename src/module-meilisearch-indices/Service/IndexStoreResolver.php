<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Service;

class IndexStoreResolver
{
    /**
     * @param string $index
     * @return int
     */
    public function resolve(string $index): int
    {
        $tab = explode('_', $index);

        return (int)$tab[count($tab) - 1];
    }
}
