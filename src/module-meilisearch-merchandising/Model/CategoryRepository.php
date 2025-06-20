<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchMerchandising\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Walkwizus\MeilisearchMerchandising\Api\CategoryRepositoryInterface;
use Walkwizus\MeilisearchMerchandising\Model\ResourceModel\Category;
use Walkwizus\MeilisearchMerchandising\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;

class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @param Category $categoryResource
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        private readonly Category $categoryResource,
        private readonly CategoryFactory $categoryFactory
    ) { }

    /**
     * @param $id
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function getBydId($id): CategoryInterface
    {
        $category = $this->categoryFactory->create();
        $this->categoryResource->load($category, $id);

        if (!$category->getId()) {
            throw new NoSuchEntityException(__('The category rule with the "%1" ID doesn\'t exist.', $id));
        }

        return $category;
    }

    /**
     * @param $categoryId
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function getByCategoryId($categoryId): CategoryInterface
    {
        $category = $this->categoryFactory->create();
        $this->categoryResource->load($category, $categoryId, CategoryInterface::CATEGORY_ID);

        if (!$category->getId()) {
            throw new NoSuchEntityException(__('The category rule with the "%1" CATEGORY ID doesn\'t exist.', $categoryId));
        }

        return $category;
    }

    /**
     * @param CategoryInterface $category
     * @return CategoryInterface
     * @throws CouldNotSaveException
     */
    public function save(CategoryInterface $category): CategoryInterface
    {
        try {
            $this->categoryResource->save($category);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $category;
    }

    /**
     * @param $categoryId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteByCategoryId($categoryId): void
    {
        try {
            $category = $this->categoryFactory->create();
            $this->categoryResource->load($category, $categoryId, CategoryInterface::CATEGORY_ID);
            $this->categoryResource->delete($category);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
    }
}
