<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Service\GetFacetList;
use Magento\Framework\Exception\LocalizedException;

class FacetConfigProvider implements ConfigProviderInterface
{
    /**
     * @param GetFacetList $facetList
     */
    public function __construct(
        private readonly GetFacetList $facetList
    ) { }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function get(): array
    {
        return [
            'facets' => $this->facetList->get()
        ];
    }
}
