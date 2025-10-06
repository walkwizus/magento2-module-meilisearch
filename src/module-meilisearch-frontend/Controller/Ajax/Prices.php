<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\Controller\Result\Json;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Framework\Pricing\Render as PriceRender;
use Magento\Framework\Exception\NoSuchEntityException;

class Prices implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param Layout $resultLayout
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly Layout $resultLayout
    ) { }

    /**
     * @return Json
     * @throws NoSuchEntityException
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $skus = (array)($this->request->getParam('skus') ?? []);
        $data = [];

        /** @var PriceRender $priceRender */
        $priceRender = $this->resultLayout->getLayout()->getBlock(PriceRender::class);
        if (!$priceRender) {
            $priceRender = $this->resultLayout->getLayout()->createBlock(
                PriceRender::class,
                PriceRender::class,
                ['data' => ['price_render_handle' => 'catalog_product_prices']]
            );
        }

        $storeId = (int)$this->storeManager->getStore()->getId();

        foreach ($skus as $sku) {
            try {
                $product = $this->productRepository->get($sku, false, $storeId);
                $html = $priceRender->render(
                    FinalPrice::PRICE_CODE,
                    $product,
                    [
                        'display_minimal_price' => true,
                        'use_link_for_as_low_as' => true,
                        'zone' => PriceRender::ZONE_ITEM_LIST,
                    ]
                );
                $data[$sku] = $html;
            } catch (\Throwable) {
                $data[$sku] = '';
            }
        }

        return $result->setData(['prices' => $data]);
    }
}
