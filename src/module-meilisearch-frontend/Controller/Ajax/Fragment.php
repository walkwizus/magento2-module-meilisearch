<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchFrontend\Model\FragmentAggregator;

class Fragment implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FragmentAggregator $aggregator
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RequestInterface $request,
        private readonly FragmentAggregator $aggregator
    ) { }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        $skus = (array)($this->request->getParam('skus') ?? []);
        $skus = array_values(array_unique(array_filter($skus)));

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->create();

        $products = $this->productRepository->getList($criteria)->getItems();
        $payload = $this->aggregator->build($products);

        return $result->setData($payload);
    }
}
