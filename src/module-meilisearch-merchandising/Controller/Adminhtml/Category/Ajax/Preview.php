<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchBase\Model\Adapter\MeilisearchFactory;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Walkwizus\MeilisearchBase\Model\Adapter\Meilisearch;

class Preview extends Action implements HttpPostActionInterface
{
    /**
     * @var Meilisearch|null
     */
    private ?Meilisearch $meilisearchClient;

    /**
     * @param Context $context
     * @param MeilisearchFactory $meilisearchFactory
     * @param QueryBuilderService $queryBuilderService
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        readonly MeilisearchFactory $meilisearchFactory,
        private readonly QueryBuilderService $queryBuilderService,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly JsonFactory $jsonFactory
    ) {
        $this->meilisearchClient = $meilisearchFactory->create();
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $rules = $this->getRequest()->getParam('rules', false);
        $json = $this->jsonFactory->create();

        if (!$rules) {
            return $json->setData([]);
        }

        $rules = json_decode($rules, true);
        $storeId = $this->getRequest()->getParam('storeId');
        $categoryId = $this->getRequest()->getParam('categoryId');

        $filters = $this->queryBuilderService->convertRulesToMeilisearchQuery($rules);
        $indexName = $this->searchIndexNameResolver->getIndexName($storeId, 'catalog_product');

        $result = $this->meilisearchClient->search($indexName, '', [
            'filter' => $filters,
            'sort' => ["category_promote.{$categoryId}:asc"],
            'limit' => 10000
        ]);

        return $json->setData($result->getHits());
    }
}
