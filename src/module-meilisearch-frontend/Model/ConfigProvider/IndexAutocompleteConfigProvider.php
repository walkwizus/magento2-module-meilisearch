<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model\ConfigProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Walkwizus\MeilisearchBase\SearchAdapter\SearchIndexNameResolver;
use Walkwizus\MeilisearchFrontend\Api\ConfigProviderInterface;
use Walkwizus\MeilisearchFrontend\Model\SourceAutocompleteConfigProvider;

class IndexAutocompleteConfigProvider implements ConfigProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param SourceAutocompleteConfigProvider $sourceAutocompleteConfigProvider
     * @param SearchIndexNameResolver $searchIndexNameResolver
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly SourceAutocompleteConfigProvider $sourceAutocompleteConfigProvider,
        private readonly SearchIndexNameResolver $searchIndexNameResolver
    ) { }

    /**
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function get(): array
    {
        $storeId =  $this->storeManager->getStore()->getId();

        $data = [];
        foreach ($this->sourceAutocompleteConfigProvider->get() as $indexerId) {
            $data[$indexerId] = $this->searchIndexNameResolver->getIndexName($storeId, $indexerId);
        }

        return [
            'autocompleteIndex' => $data
        ];
    }
}
