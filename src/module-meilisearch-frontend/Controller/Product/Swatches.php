<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Product;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchRenderer;
use Magento\Framework\View\LayoutInterface;

class Swatches implements HttpGetActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LayoutInterface $layout
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LayoutInterface $layout
    ) {}

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $productIds = $this->request->getParam('product_ids', []);
            if (!is_array($productIds)) {
                $productIds = [$productIds];
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $productIds, 'in')
                ->create();

            $products = $this->productRepository->getList($searchCriteria)->getItems();

            $swatchData = [];
            foreach ($products as $product) {
                if ($product->getTypeId() === Configurable::TYPE_CODE) {
                    /** @var SwatchRenderer $swatchBlock */
                    $swatchBlock = $this->layout->createBlock(SwatchRenderer::class);
                    $swatchBlock->setProduct($product);
                    $swatchData[$product->getId()] = json_decode($swatchBlock->getJsonSwatchConfig(), true);
                }
            }

            return $result->setData($swatchData);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => $e->getMessage()
            ]);
        }
    }
}
