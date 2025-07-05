<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Controller\Adminhtml\Facet;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $facetConfigs = json_decode($this->getRequest()->getParam('facets', []), true);

            if (!is_array($facetConfigs)) {
                throw new \InvalidArgumentException('Invalid data format');
            }

            foreach ($facetConfigs as $facetConfig) {
                try {
                    $attribute = $this->productAttributeRepository->get($facetConfig['code']);

                    $attribute->setPosition($facetConfig['position']);
                    $attribute->setMeilisearchShowMore($facetConfig['show_more']);
                    $attribute->setMeilisearchShowMoreLimit($facetConfig['show_more_limit']);
                    $attribute->setMeilisearchSearchable($facetConfig['searchable']);
                    $attribute->setMeilisearchSearchboxFuzzyEnabled($facetConfig['searchbox_fuzzy_enabled']);
                    $attribute->setMeilisearchSortValuesBy($facetConfig['sort_values_by']);

                    $this->productAttributeRepository->save($attribute);
                } catch (\Exception $e) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __($e->getMessage())
                    ]);
                }
            }

            return $resultJson->setData([
                'success' => true,
                'message' => __('Configuration saved successfully')
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Walkwizus_MeilisearchMerchandising::merchandising');
    }
}
