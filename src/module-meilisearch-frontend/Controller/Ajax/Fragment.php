<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Walkwizus\MeilisearchFrontend\Model\FragmentAggregator;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\Result\Json;

class Fragment implements HttpPostActionInterface
{
    /**
     * @var array|string[]
     */
    private array $attributesToSelect = [
        'entity_id',
        'type_id',
        'price',
        'special_price',
        'special_from_date',
        'special_to_date',
        'tax_class_id',
        'status',
        'visibility'
    ];

    /**
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param FragmentAggregator $aggregator
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly RequestInterface $request,
        private readonly FragmentAggregator $aggregator,
        private readonly CollectionFactory $collectionFactory
    ) { }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        $skus = (array)($this->request->getParam('skus') ?? []);
        $skus = array_values(array_unique(array_filter($skus)));

        $products = $this->collectionFactory
            ->create()
            ->addAttributeToSelect($this->attributesToSelect)
            ->addAttributeToFilter('sku', ['in' => $skus]);

        $payload = $this->aggregator->build($products);

        return $result->setData($payload);
    }
}
