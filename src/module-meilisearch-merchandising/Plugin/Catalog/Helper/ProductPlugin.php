<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Plugin\Catalog\Helper;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Framework\Registry;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Session as CatalogSession;
use Walkwizus\MeilisearchBase\Service\DocumentsManager;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Store\Model\StoreManagerInterface;

class ProductPlugin
{
    /**
     * @param Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CatalogSession $catalogSession
     * @param DocumentsManager $documentsManager
     * @param SearchIndexNameResolver $indexNameResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CatalogSession $catalogSession,
        private readonly DocumentsManager $documentsManager,
        private readonly SearchIndexNameResolver $indexNameResolver,
        private readonly StoreManagerInterface $storeManager
    ) { }

    /**
     * @param ProductHelper $subject
     * @param $product
     * @return mixed
     */
    public function afterInitProduct(ProductHelper $subject, $product): mixed
    {
        if (!$product) {
            return $product;
        }

        if ($this->registry->registry('current_category')) {
            return $product;
        }

        $categoryId = $this->catalogSession->getLastVisitedCategoryId();

        if (!$categoryId) {
            $categoryId = $this->getCategoryIdFromMeilisearch((int)$product->getId());
        }

        if ($categoryId) {
            try {
                $category = $this->categoryRepository->get($categoryId);
                if ($category->getIsActive()) {
                    $product->setCategory($category);

                    if (!$this->registry->registry('current_category')) {
                        $this->registry->register('current_category', $category);
                    }
                }
            } catch (\Exception $e) {

            }
        }

        return $product;
    }

    /**
     * @param int $productId
     * @return int|null
     */
    private function getCategoryIdFromMeilisearch(int $productId): ?int
    {
        try {
            $store = $this->storeManager->getStore();
            $storeId = $store->getId();
            $rootCategoryId = $store->getRootCategoryId();

            $indexName = $this->indexNameResolver->getIndexName($storeId, 'catalog_product');
            $document = $this->documentsManager->getDocument($indexName, (string)$productId, ['category_ids']);

            if (isset($document['category_ids']) && is_array($document['category_ids'])) {
                $ids = array_filter($document['category_ids'], function($id) use ($rootCategoryId) {
                    return (int)$id > $rootCategoryId;
                });

                return !empty($ids) ? (int)end($ids) : null;
            }
        } catch (\Exception $e) {

        }

        return null;
    }
}
