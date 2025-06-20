<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Walkwizus\MeilisearchMerchandising\Api\Data\CategoryInterface;

interface CategoryRepositoryInterface
{
    /**
     * @param int $id
     * @return CategoryInterface
     */
    public function getBydId($id): CategoryInterface;

    /**
     * @param int $categoryId
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function getByCategoryId($categoryId): CategoryInterface;

    /**
     * @param CategoryInterface $category
     * @return CategoryInterface
     */
    public function save(CategoryInterface $category): CategoryInterface;

    /**
     * @param $categoryId
     * @return void
     */
    public function deleteByCategoryId($categoryId): void;
}
