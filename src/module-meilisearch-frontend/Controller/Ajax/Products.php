<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Walkwizus\MeilisearchFrontend\Model\Search\SearchService;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Walkwizus\MeilisearchFrontend\Block\Ajax\ProductList;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;
use Magento\PageCache\Model\Config as PageCacheConfig;
use Hyva\Theme\Service\CurrentTheme;

class Products extends Action implements HttpGetActionInterface
{

    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly LayoutFactory $layoutFactory,
        private readonly SearchService $searchService,
        private readonly CollectionFactory $collectionFactory,
        private readonly ServerSettings $serverSettings,
        private readonly PageCacheConfig $pageCacheConfig,
        private readonly CurrentTheme $currentTheme,
        Context $context
    ) {
        return parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $meta = [];
        $html = '';

        if ($this->serverSettings->getCatalogListMode() === ServerSettings::MEILISEARCH_SERVER_SIDE_CATALOG_LIST) {

            $searchResult = $this->searchService->getSearchResult();
            $hits = $searchResult['hits'] ?? [];

            $meta = [
                'totalHits' => (int)($searchResult['totalHits'] ?? 0),
                'totalPages' => (int)($searchResult['totalPages'] ?? 0),
                'page' => (int)($searchResult['page'] ?? 1),
                'hitsPerPage' => (int)($searchResult['hitsPerPage'] ?? 0),
                'facetDistribution' => $searchResult['facetDistribution'] ?? [],
                'facetStats' => $searchResult['facetStats'] ?? [],
            ];

            if (empty($hits)) {
                return $result->setData(array_merge($meta, ['html' => '']));
            }

            $entityIds = array_values(array_filter(array_map(
                static fn(array $hit): int => (int)($hit['id'] ?? 0),
                $hits
            )));

            if (empty($entityIds)) {
                return $result->setData(array_merge($meta, ['html' => '']));
            }

            $collection = $this->collectionFactory->create()
                ->addAttributeToFilter('entity_id', ['in' => $entityIds])
                ->addUrlRewrite();

            $idList = implode(',', $entityIds);
            $collection->getSelect()->order(new \Zend_Db_Expr("FIELD(e.entity_id, $idList)"));

            $layout = $this->layoutFactory->create();
            $layout->getUpdate()->load([
                'default',
                'catalog_category_view',
                'catalog_list_item',
                'meilisearch_ajax_products'
            ]);
            $layout->generateXml();
            $layout->generateElements();

            /** @var ProductList|false $block */
            $block = $layout->getBlock('meilisearch.ajax.product.list');
            if ($this->currentTheme->isHyva()) {
                $block->setTemplate('Hyva_WalkwizusMeilisearch::product/list.phtml');
            }
            $formKeyBlock = $layout->createBlock(
                \Magento\Framework\View\Element\FormKey::class
            );
            $layout->setBlock('formkey', $formKeyBlock);

            if (!$block) {
                return $result->setData(array_merge($meta, ['html' => '']));
            }

            $block->setCollection($collection);
            $html = $block->toHtml();
        }

        $categoryIds = $this->getRequest()->getParam('category_ids');
        if ($this->pageCacheConfig->isEnabled()) {
            if (!$categoryIds) {
                $this->getResponse()->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
            } else {
                $ttl = $this->pageCacheConfig->getTtl();
                $this->getResponse()->setPublicHeaders($ttl);

                $tags = [];
                foreach (explode('|', $categoryIds) as $categoryId) {
                    $tags[] = \Magento\Catalog\Model\Category::CACHE_TAG . '_' . (int)$categoryId;
                    $tags[] = \Magento\Catalog\Model\Category::CACHE_TAG . '_p_' . (int)$categoryId;
                }
                foreach ($entityIds as $entityId) {
                    $tags[] = \Magento\Catalog\Model\Product::CACHE_TAG . '_' . (int)$entityId;
                }
                $this->getResponse()->setHeader('X-Magento-Tags', implode(',', $tags));
            }
        }

        return $result->setData(array_merge($meta, ['html' => $html]));
    }
}
