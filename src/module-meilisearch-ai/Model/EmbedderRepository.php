<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Walkwizus\MeilisearchAi\Api\EmbedderRepositoryInterface;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder as EmbedderResource;
use Walkwizus\MeilisearchAi\Model\ResourceModel\Embedder\CollectionFactory;

class EmbedderRepository implements EmbedderRepositoryInterface
{
    /**
     * @param EmbedderFactory $embedderFactory
     * @param EmbedderResource $resource
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param HydratorInterface $hydrator
     */
    public function __construct(
        private readonly EmbedderFactory $embedderFactory,
        private readonly EmbedderResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly HydratorInterface $hydrator
    ) { }

    /**
     * @param EmbedderInterface $embedder
     * @return EmbedderInterface
     * @throws CouldNotSaveException
     */
    public function save(EmbedderInterface $embedder): EmbedderInterface
    {
        try {
            if ($embedder->getId()) {
                $existingEmbedder = $this->getById($embedder->getId());
                $embedder = $this->hydrator->hydrate(
                    $existingEmbedder,
                    $this->hydrator->extract($embedder)
                );
            }
            $this->resource->save($embedder);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $embedder;
    }

    /**
     * @param int|string $embedderId
     * @return EmbedderInterface
     * @throws NoSuchEntityException
     */
    public function getById($embedderId): EmbedderInterface
    {
        $embedder = $this->embedderFactory->create();
        $this->resource->load($embedder, $embedderId);

        if (!$embedder->getId()) {
            throw new NoSuchEntityException(__('Embedder with ID "%1" does not exist.', $embedderId));
        }

        return $embedder;
    }

    /**
     * @param EmbedderInterface $embedder
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(EmbedderInterface $embedder): bool
    {
        try {
            $this->resource->delete($embedder);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @param $embedderId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($embedderId): bool
    {
        return $this->delete($this->getById($embedderId));
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed|EmbedderResource\Collection
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        return $collection;
    }
}
