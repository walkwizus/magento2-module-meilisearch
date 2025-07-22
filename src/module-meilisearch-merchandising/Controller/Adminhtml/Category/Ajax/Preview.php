<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Category\Ajax;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Walkwizus\MeilisearchBase\Service\SearchManager;
use Walkwizus\MeilisearchMerchandising\Service\QueryBuilderService;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class Preview extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param SearchManager $searchManager
     * @param QueryBuilderService $queryBuilderService
     * @param SearchIndexNameResolver $searchIndexNameResolver
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        private readonly SearchManager $searchManager,
        private readonly QueryBuilderService $queryBuilderService,
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     * @throws \Exception
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

        $result = $this->searchManager->search($indexName, '', [
            'filter' => $filters,
            'sort' => ["category_promote.{$categoryId}:asc"],
            'limit' => 10000
        ]);

        return $json->setData($result->getHits());
    }
}
