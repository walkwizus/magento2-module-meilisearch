<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Block\Ajax;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductList extends ListProduct
{
    protected $_productCollection = null;

    protected bool $collectionPrepared = false;

    /**
     * @return Collection
     */
    protected function _getProductCollection(): Collection
    {
        if ($this->_productCollection !== null) {
            if (!$this->collectionPrepared) {
                $this->_addProductAttributesAndPrices($this->_productCollection);
                $this->collectionPrepared = true;
            }
            return $this->_productCollection;
        }

        return parent::_getProductCollection();
    }
}
