<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory;

class EmbedderOptions implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();

        /** @var \Walkwizus\MeilisearchAi\Model\Embedder $embedder */
        foreach ($collection as $embedder) {
            $options[] = [
                'value' => $embedder->getId(),
                'label' => sprintf('%s (%s)', $embedder->getName(), $embedder->getSource())
            ];
        }

        return $options;
    }
}
