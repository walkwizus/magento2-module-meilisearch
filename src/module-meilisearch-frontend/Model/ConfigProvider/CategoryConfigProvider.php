<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;
use Magento\Catalog\Helper\Product\ProductList;

class CategoryConfigProvider implements ConfigProviderInterface
{
    /**
     * @param LayerResolver $layerResolver
     * @param AttributeResolver $attributeResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param QueryBuilderService $queryBuilderService
     * @param ProductList $productList
     */
    public function __construct(
        private readonly LayerResolver $layerResolver,
        private readonly AttributeResolver $attributeResolver,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly QueryBuilderService $queryBuilderService,
        private readonly ProductList $productList
    ) { }

    /**
     * @return array
     */
    public function get(): array
    {
        $layer = $this->layerResolver->get();
        $category = $layer->getCurrentCategory();

        if (!$category) {
            return [];
        }

        $availableSortBy = $category->getAvailableSortByOptions();

        if (!$availableSortBy) {
            $availableSortBy = $category->getDefaultSortBy();
        }

        $resolvedSortBy = [];
        foreach ($availableSortBy as $code => $label) {
            $resolvedCode = $this->attributeResolver->resolve($code);
            $resolvedSortBy[$resolvedCode] = __($label);
        }

        $data = [
            'currentCategoryId' => $category->getId(),
            'currentCategoryUrl' => $category->getUrl(),
            'availableSortBy' => $resolvedSortBy,
            'defaultSortBy' => $this->attributeResolver->resolve($category->getDefaultSortBy()),
            'availableViewMode' => $this->productList->getAvailableViewMode()
        ];

        try {
            $categoryRule = $this->categoryRepository->getByCategoryId($category->getId());
            $meilisearchQuery = $this->queryBuilderService->convertRulesToMeilisearchQuery(json_decode($categoryRule->getQuery(), true));
            if ($categoryRule->getCategoryId()) {
                $data['categoryRule'] = $meilisearchQuery;
                unset($data['currentCategoryId']);
            }
        } catch (\Exception $e) { }

        return $data;
    }
}
