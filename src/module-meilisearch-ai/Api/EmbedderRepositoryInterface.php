<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchAi\Api;

use Walkwizus\MeilisearchAi\Api\Data\EmbedderInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface EmbedderRepositoryInterface
{
    /**
     * @param EmbedderInterface $embedder
     * @return mixed
     */
    public function save(EmbedderInterface $embedder);

    /**
     * @param $embedderId
     * @return EmbedderInterface
     */
    public function getById($embedderId): EmbedderInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return mixed
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param EmbedderInterface $embedder
     * @return mixed
     */
    public function delete(EmbedderInterface $embedder);

    /**
     * @param $embedderId
     * @return mixed
     */
    public function deleteById($embedderId);
}
