<?php

declare(strict_types=1);

namespace BA\MeilisearchFrontendMinimal\Plugin\GoogleTagManager\Block;

use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Walkwizus\MeilisearchFrontend\ViewModel\Ssr;

class ListJson
{
    private const MEILISEARCH_RESULT_HANDLE = 'meilisearch_result';

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly Ssr $ssrViewModel
    ) {
    }

    public function afterGetLoadedProductCollection(
        \Magento\GoogleTagManager\Block\ListJson $subject,
        mixed $result
    ): mixed {
        if ($result !== null || !$this->isMeilisearchResponsePage($subject)) {
            return $result;
        }

        try {
            $searchResult = $this->ssrViewModel->getSearchResult();
        } catch (\Throwable $exception) {
            return $result;
        }

        if (!isset($searchResult['hits']) || !is_array($searchResult['hits'])) {
            return $result;
        }

        $collection = $this->collectionFactory->create();

        foreach ($searchResult['hits'] as $hit) {
            if (!is_array($hit)) {
                continue;
            }

            $hit['sku'] = (string)($hit['sku'] ?? '');
            $hit['name'] = (string)($hit['name'] ?? '');
            $hit['type_id'] = (string)($hit['type_id'] ?? '');

            $collection->addItem(new DataObject($hit));
        }

        if ($collection->count() === 0) {
            return $result;
        }

        $collection->setCurPage(max(1, (int)($searchResult['page'] ?? 1)));

        $hitsPerPage = (int)($searchResult['hitsPerPage'] ?? 0);
        if ($hitsPerPage > 0) {
            $collection->setPageSize($hitsPerPage);
        }

        return $collection;
    }

    /**
     * @param \Magento\GoogleTagManager\Block\ListJson $subject
     * @return bool
     */
    private function isMeilisearchResponsePage(\Magento\GoogleTagManager\Block\ListJson $subject): bool
    {
        return in_array(
            self::MEILISEARCH_RESULT_HANDLE,
            $subject->getLayout()->getUpdate()->getHandles(),
            true
        );
    }
}
