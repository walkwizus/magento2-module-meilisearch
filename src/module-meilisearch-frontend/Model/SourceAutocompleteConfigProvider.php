<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

class SourceAutocompleteConfigProvider
{
    /**
     * @param array $sources
     */
    public function __construct(
        private readonly array $sources
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->sources;
    }
}
