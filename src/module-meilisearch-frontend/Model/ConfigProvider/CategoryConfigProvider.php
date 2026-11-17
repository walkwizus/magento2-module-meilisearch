<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\RankSortState;
use Walkwizus\MeilisearchMerchandising\Model\CategoryProduct\SortFields;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\CategoryProductPosition;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;

class CategoryConfigProvider implements ConfigProviderInterface
{
    /**
     * @param LayerResolver $layerResolver
     * @param AttributeResolver $attributeResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param QueryBuilderService $queryBuilderService
     * @param ProductList $productList
     * @param CategoryProductPosition $categoryProductPosition
     * @param SortFields $sortFields
     * @param StoreManagerInterface $storeManager
     * @param RankSortState $rankSortState
     */
    public function __construct(
        private readonly LayerResolver $layerResolver,
        private readonly AttributeResolver $attributeResolver,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly QueryBuilderService $queryBuilderService,
        private readonly ProductList $productList,
        private readonly CategoryProductPosition $categoryProductPosition,
        private readonly SortFields $sortFields,
        private readonly StoreManagerInterface $storeManager,
        private readonly RankSortState $rankSortState
    ) {
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
            'availableViewMode' => $this->productList->getAvailableViewMode(),
            'defaultViewMode' => $this->productList->getDefaultViewMode(),
            'sortFields' => $this->getSortFields((int) $category->getId()),
        ];

        try {
            $categoryRule = $this->categoryRepository->getByCategoryId($category->getId());
            $meilisearchQuery = $this->queryBuilderService->convertRulesToMeilisearchQuery(json_decode($categoryRule->getQuery(), true));
            if ($categoryRule->getCategoryId()) {
                $data['categoryRule'] = $meilisearchQuery;
                unset($data['currentCategoryId']);
            }
        } catch (\Exception $e) {
        }

        return $data;
    }

    /**
     * @param int $categoryId
     * @return array|array[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSortFields(int $categoryId): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        if (
            !$this->rankSortState->isReady()
            || !in_array($categoryId, $this->categoryProductPosition->getConfiguredCategoryIds($storeId), true)
        ) {
            return [];
        }

        $positionField = $this->sortFields->getPositionField($categoryId);

        return [$positionField => [$this->sortFields->getRankField($categoryId), $positionField]];
    }
}
