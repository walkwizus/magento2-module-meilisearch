<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Layer\Search as SearchLayer;

class SearchConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    public const SORT_RELEVANCE = 'relevance';

    private const POSITION_ATTRIBUTE = 'position';

    /**
     * @param LayerResolver $layerResolver
     * @param AttributeResolver $attributeResolver
     */
    public function __construct(
        private readonly LayerResolver $layerResolver,
        private readonly AttributeResolver $attributeResolver
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        $layer = $this->layerResolver->get();

        if (!$layer instanceof SearchLayer) {
            return [];
        }

        return [
            'currentCategoryId' => null,
            'categoryRule' => '',
            'availableSortBy' => $this->getAvailableSortBy($layer),
            'defaultSortBy' => self::SORT_RELEVANCE,
        ];
    }

    /**
     * @param SearchLayer $layer
     * @return array
     */
    private function getAvailableSortBy(SearchLayer $layer): array
    {
        $availableSortBy = (array) $layer->getCurrentCategory()->getAvailableSortByOptions();

        unset($availableSortBy[self::POSITION_ATTRIBUTE]);

        $resolvedSortBy = [];
        foreach ($availableSortBy as $code => $label) {
            $resolvedSortBy[$this->attributeResolver->resolve((string) $code)] = __($label);
        }

        $resolvedSortBy[self::SORT_RELEVANCE] = __('Relevance');

        return $resolvedSortBy;
    }
}
