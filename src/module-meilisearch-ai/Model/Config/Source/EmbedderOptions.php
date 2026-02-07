<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory;
use Walkwizus\MeilisearchAi\Model\Embedder;

class EmbedderOptions implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) { }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();

        /** @var Embedder $embedder */
        foreach ($collection as $embedder) {
            $options[] = [
                'label' => sprintf('%s (%s)', $embedder->getName(), $embedder->getSource()),
                'value' => $embedder->getId()
            ];
        }

        return $options;
    }
}
