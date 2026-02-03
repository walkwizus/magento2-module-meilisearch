<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory;

class EmbedderForm extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param $name
     * @param $primaryFieldName
     * @param $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $embedder) {
            $this->loadedData[$embedder->getId()] = $embedder->getData();
        }

        return $this->loadedData ?? [];
    }
}
