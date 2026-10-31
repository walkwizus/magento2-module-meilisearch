<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchBase\Model\AttributeProvider;
use Walkwizus\MeilisearchBase\Model\AttributeResolver;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Swatches\Model\Swatch;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchMerchandising\Api\CategoryFacetOrderingResolverInterface;
use Walkwizus\MeilisearchMerchandising\Model\CategoryFacet\OrderingApplier;

class FacetConfigProvider implements ConfigProviderInterface
{
    /**
     * @var array|string[]
     */
    private array $regionMapping = [
        'select' => 'checkbox',
        'multiselect' => 'checkbox',
        'boolean' => 'checkbox',
        'price' => 'price',
        'swatch_visual' => 'swatch',
        'swatch_text' => 'swatch'
    ];

    /**
     * @param AttributeProvider $attributeProvider
     * @param AttributeResolver $attributeResolver
     * @param AttributeFactory $attributeFactory
     * @param SwatchHelper $swatchHelper
     * @param HttpRequest $request
     * @param LayerResolver $layerResolver
     * @param StoreManagerInterface $storeManager
     * @param CategoryFacetOrderingResolverInterface $categoryFacetOrderingResolver
     * @param OrderingApplier $orderingApplier
     */
    public function __construct(
        private readonly AttributeProvider $attributeProvider,
        private readonly AttributeResolver $attributeResolver,
        private readonly AttributeFactory $attributeFactory,
        private readonly SwatchHelper $swatchHelper,
        private readonly HttpRequest $request,
        private readonly LayerResolver $layerResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryFacetOrderingResolverInterface $categoryFacetOrderingResolver,
        private readonly OrderingApplier $orderingApplier
    ) { }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function get(): array
    {
        $attributes = $this->attributeProvider->getFilterableAttributes(SearchIndexNameResolver::DEFAULT_INDEX, 'frontend');
        $processedFacets = $this->getFacetList($attributes);
        $facetList = array_keys($processedFacets);
        $category = $this->getCurrentCategory();

        if ($category !== null && !$category->getData('is_anchor')) {
            $facetList = [];
        } elseif ($category !== null) {
            $categoryOrdering = $this->categoryFacetOrderingResolver->resolve(
                (int) $this->storeManager->getStore()->getId(),
                (int) $category->getId()
            );

            if ($categoryOrdering !== null) {
                [$facetList, $processedFacets] = $this->orderingApplier->apply($facetList, $processedFacets, $categoryOrdering);
            }
        }

        return [
            'facets' => [
                'facetList' => $facetList,
                'facetConfig' => $processedFacets
            ]
        ];
    }

    /**
     * @return Category|null
     */
    private function getCurrentCategory(): ?Category
    {
        if ($this->request->getFullActionName() !== 'catalog_category_view') {
            return null;
        }

        try {
            $category = $this->layerResolver->get()->getCurrentCategory();
        } catch (\Exception $e) {
            return null;
        }

        return $category->getId() ? $category : null;
    }

    /**
     * @param array $attributes
     * @return array
     * @throws LocalizedException
     */
    public function getFacetList(array $attributes): array
    {
        $processedFacets = [];

        foreach ($attributes as $attributeCode) {
            $attributeConfig = $this->getAttributeConfig($attributeCode);
            if ($attributeConfig) {
                $resolvedCode = $this->attributeResolver->resolve($attributeCode);
                $processedFacets[$resolvedCode] = $attributeConfig;
            }
        }

        return $processedFacets;
    }

    /**
     * @param string $attributeCode
     * @return array|null
     * @throws LocalizedException
     */
    private function getAttributeConfig(string $attributeCode): ?array
    {
        $attribute = $this->attributeFactory->create()->loadByCode('catalog_product', $attributeCode);

        if (!$attribute->getId()) {
            return null;
        }

        $frontendInput = $attribute->getFrontendInput();
        $renderRegion = $this->determineRenderRegion($attribute);
        $useSource = $attribute->usesSource();

        return [
            'code' => $this->attributeResolver->resolve($attributeCode),
            'position' => (int)$attribute->getPosition(),
            'showMore' => (bool)$attribute->getMeilisearchShowMore(),
            'showMoreLimit' => (int)$attribute->getMeilisearchShowMoreLimit(),
            'searchable' => (bool)$attribute->getMeilisearchSearchable(),
            'searchboxFuzzyEnabled' => (bool)$attribute->getMeilisearchSearchboxFuzzyEnabled(),
            'sortValuesBy' => $attribute->getMeilisearchSortValuesBy(),
            'hideIfNonDiscriminant' => (bool)$attribute->getMeilisearchHideIfNonDiscriminant(),
            'label' => $attribute->getStoreLabel(),
            'type' => $frontendInput,
            'renderRegion' => $renderRegion,
            'hasOptions' => $useSource,
            'isSwatch' => $this->swatchHelper->isSwatchAttribute($attribute)
        ];
    }

    /**
     * @param Attribute $attribute
     * @return string
     */
    private function determineRenderRegion(Attribute $attribute): string
    {
        if ($this->swatchHelper->isSwatchAttribute($attribute)) {
            $swatchType = $attribute->getData(Swatch::SWATCH_INPUT_TYPE_KEY);
            return $this->regionMapping["swatch_" . $swatchType] ?? 'checkbox';
        }

        $frontendInput = $attribute->getFrontendInput();

        return $this->regionMapping[$frontendInput] ?? 'checkbox';
    }
}
