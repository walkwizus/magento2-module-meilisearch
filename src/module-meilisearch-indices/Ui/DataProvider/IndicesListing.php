<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchIndices\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Walkwizus\MeilisearchBase\Service\IndexesManager;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchIndices\Service\IndexStoreResolver;
use Magento\Framework\Exception\NoSuchEntityException;

class IndicesListing extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param IndexesManager $indexesManager
     * @param StoreManagerInterface $storeManager
     * @param IndexStoreResolver $indexStoreResolver
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly IndexesManager $indexesManager,
        private readonly StoreManagerInterface $storeManager,
        private readonly IndexStoreResolver $indexStoreResolver,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function getData(): array
    {
        $indexes = $this->indexesManager->getIndexes()->toArray();

        $data = [];

        foreach ($indexes['results'] as $index) {
            $uid = $index->getUid();
            $storeId = $this->indexStoreResolver->resolve($uid);
            $store = $this->storeManager->getStore($storeId);

            $data['items'][] = [
                'id' => $uid,
                'store' => $store->getName() . ' (' . $store->getCode() . ')',
            ];
        }

        $data['totalRecords'] = $indexes['total'];

        return $data;
    }
}
