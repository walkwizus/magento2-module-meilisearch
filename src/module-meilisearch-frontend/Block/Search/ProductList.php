<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Block\Search;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Walkwizus\MeilisearchFrontend\Block\Ajax\ProductList as AjaxProductList;
use Walkwizus\MeilisearchFrontend\ViewModel\Ssr;

class ProductList extends AjaxProductList
{
    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        Category $categoryHelper,
        CategoryRepositoryInterface $categoryRepository,
        UrlHelper $urlHelper,
        private readonly Ssr $ssr,
        private readonly CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    protected function _getProductCollection(): Collection
    {
        if ($this->_productCollection !== null) {
            return parent::_getProductCollection();
        }

        $searchResult = $this->ssr->getSearchResult();
        $hits = $searchResult['hits'] ?? [];

        $entityIds = array_values(array_filter(array_map(
            static fn(array $hit): int => (int)($hit['id'] ?? 0),
            $hits
        )));

        $collection = $this->collectionFactory->create()->addUrlRewrite();

        if (!empty($entityIds)) {
            $collection->addAttributeToFilter('entity_id', ['in' => $entityIds]);
            $idList = implode(',', $entityIds);
            $collection->getSelect()->order(new \Zend_Db_Expr("FIELD(e.entity_id, $idList)"));
        } else {
            // Return empty collection when no Meilisearch results
            $collection->addAttributeToFilter('entity_id', ['in' => [0]]);
        }

        $this->setProductCollection($collection);

        return parent::_getProductCollection();
    }

}
