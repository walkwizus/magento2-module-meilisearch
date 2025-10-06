<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Layout;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable;
use Magento\Swatches\ViewModel\Product\Renderer\Configurable as ConfigurableViewModel;

class Swatches implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param Layout $resultLayout
     * @param ConfigurableViewModel $configurableViewModel
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RequestInterface $request,
        private readonly Layout $resultLayout,
        private readonly ConfigurableViewModel $configurableViewModel
    ) { }

    /**
     * @return Json
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $skus = (array)($this->request->getParam('skus') ?? []);
        $data = [];

        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $block = $this->resultLayout->getLayout()
                    ->createBlock(
                        Configurable::class,
                        '',
                        [
                            'data' => [
                                'configurable_view_model' => $this->configurableViewModel
                            ]
                        ]
                    )
                    ->setProduct($product)
                    ->setTemplate('Magento_Swatches::product/listing/renderer.phtml');

                $data[$sku] = $block->toHtml();
            }
        }

        return $result->setData(['swatches' => $data]);
    }
}
