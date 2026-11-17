<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_MATCHING_STRATEGY = 'catalog/search/meilisearch_matching_strategy';
    private const XML_PATH_FALLBACK_STRATEGY = 'catalog/search/meilisearch_fallback_strategy';
    private const XML_PATH_EXACT_WORDS_FIRST = 'catalog/search/meilisearch_exact_words_first';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) { }

    /**
     * @param int $storeId
     * @return string
     */
    public function getMatchingStrategy(int $storeId): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_MATCHING_STRATEGY, ScopeInterface::SCOPE_STORE, $storeId) ?: 'last';
    }

    /**
     * @param int $storeId
     * @return string|null null when the search should not be retried
     */
    public function getFallbackStrategy(int $storeId): ?string
    {
        $strategy = (string)$this->scopeConfig->getValue(self::XML_PATH_FALLBACK_STRATEGY, ScopeInterface::SCOPE_STORE, $storeId);

        return $strategy !== '' && $strategy !== $this->getMatchingStrategy($storeId) ? $strategy : null;
    }

    /**
     * @param int $storeId
     * @return bool
     */
    public function isExactWordsFirst(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EXACT_WORDS_FIRST, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
