<?php
namespace BA\MeilisearchFrontendMinimal\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class SearchForm implements ArgumentInterface
{
    private UrlInterface $url;
    private RequestInterface $request;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        UrlInterface $url,
        RequestInterface $request,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->url = $url;
        $this->request = $request;
        $this->categoryRepository = $categoryRepository;
    }

    public function getCatalogSearchActionUrl(): string
    {
        return $this->url->getUrl('catalogsearch/result');
    }

    /**
     * Will attempt to query the current category page first, then default to catalogsearch.
     */
    public function getActionUrl(): string
    {
        if ($this->isCategoryPage()) {
            $category = $this->getCurrentCategory();
            if ($category) {
                $categoryUrl = (string) $category->getUrl();
                if ($categoryUrl !== '') {
                    return $categoryUrl;
                }
            }
        }

        return $this->getCatalogSearchActionUrl();
    }

    private function isCategoryPage(): bool
    {
        return $this->request->getFullActionName() === 'catalog_category_view';
    }

    private function getCurrentCategory(): ?CategoryInterface
    {
        $categoryId = (int) $this->request->getParam('id');
        if ($categoryId <= 0) {
            return null;
        }

        try {
            return $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
