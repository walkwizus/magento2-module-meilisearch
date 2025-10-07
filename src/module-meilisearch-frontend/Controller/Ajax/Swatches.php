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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Walkwizus\MeilisearchFrontend\Model\Config\StoreFront;
use Magento\Store\Model\StoreManagerInterface;

class Swatches implements HttpPostActionInterface
{
    /**
     * @param JsonFactory $jsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param Layout $resultLayout
     * @param ConfigurableViewModel $configurableViewModel
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreFront $storeFront
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RequestInterface $request,
        private readonly Layout $resultLayout,
        private readonly ConfigurableViewModel $configurableViewModel,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StoreFront $storeFront,
        private readonly StoreManagerInterface $storeManager
    ) { }

    /**
     * @return Json
     * @throws NoSuchEntityException
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $storeId = $this->storeManager->getStore()->getId();

        if (!$this->storeFront->getShowSwatchesInProductList($storeId)) {
            return $result->setData(['swatches' => []]);
        }

        $skus = (array)($this->request->getParam('skus') ?? []);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('type_id', \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
            ->addFilter('sku', $skus, 'in')
            ->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();

        $swatchData = [];
        foreach ($products as $product) {
            $block = $this->resultLayout->getLayout()
                ->createBlock(
                    Configurable::class,
                    'category.product.type.details.renderers.configurable',
                    [
                        'data' => [
                            'configurable_view_model' => $this->configurableViewModel
                        ]
                    ]
                )
                ->setProduct($product)
                ->setTemplate('Magento_Swatches::product/listing/renderer.phtml');

            $swatchData[$product->getSku()] = $block->toHtml();
        }

        return $result->setData(['swatches' => $swatchData]);
    }
}
