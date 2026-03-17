<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Walkwizus\MeilisearchFrontend\Model\Search\SearchService;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Walkwizus\MeilisearchFrontend\Block\Ajax\ProductList;
use Walkwizus\MeilisearchBase\Model\Config\ServerSettings;

class Products implements HttpGetActionInterface
{

    /**
     * @param JsonFactory $jsonFactory
     * @param LayoutFactory $layoutFactory
     * @param SearchService $searchService
     * @param CollectionFactory $collectionFactory
     * @param ServerSettings $serverSettings
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly LayoutFactory $layoutFactory,
        private readonly SearchService $searchService,
        private readonly CollectionFactory $collectionFactory,
        private readonly ServerSettings $serverSettings
    ) {}

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
            $layout->getUpdate()->load(['', 'meilisearch_ajax_products']);
            $layout->generateXml();
            $layout->generateElements();

            /** @var ProductList|false $block */
            $block = $layout->getBlock('meilisearch.ajax.product.list');

            if (!$block) {
                return $result->setData(array_merge($meta, ['html' => '']));
            }

            $block->setProductCollection($collection);
            $html = $block->toHtml();
        }

        return $result->setData(array_merge($meta, ['html' => $html]));
    }
}
