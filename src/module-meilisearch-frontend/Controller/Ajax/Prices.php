<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\Controller\Result\Json;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Render as PriceRender;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Prices implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param Layout $resultLayout
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RequestInterface $request,
        private readonly Layout $resultLayout,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) { }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $skus = (array)($this->request->getParam('skus') ?? []);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();

        $priceData = [];
        foreach ($products as $product) {
            $priceRender = $this->resultLayout->getLayout()->createBlock(
                PriceRender::class,
                '',
                [
                    'data' => [
                        'price_render_handle' => 'catalog_product_prices'
                    ]
                ]
            );

            $html = $priceRender->render(
                FinalPrice::PRICE_CODE,
                $product,
                [
                    'display_minimal_price' => true,
                    'use_link_for_as_low_as' => true,
                    'zone' => PriceRender::ZONE_ITEM_LIST,
                ]
            );
            $priceData[$product->getSku()] = $html;
        }

        return $result->setData(['prices' => $priceData]);
    }
}
