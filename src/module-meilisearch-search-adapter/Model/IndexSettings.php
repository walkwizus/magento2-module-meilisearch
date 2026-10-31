<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchSearchAdapter\Model;

use Magento\Framework\App\CacheInterface;
use Walkwizus\MeilisearchBase\SearchAdapter\ConnectionManager;

/**
 * Requests can name fields the index does not have yet, and Meilisearch rejects the whole search for one.
 */
class IndexSettings
{
    private const CACHE_KEY_PREFIX = 'MEILISEARCH_SEARCH_ADAPTER';
    // settings apply asynchronously, so invalidating on save would race them
    private const CACHE_LIFETIME = 60;

    /**
     * @var array<string, array<string, string[]>>
     */
    private array $settings = [];

    /**
     * @param ConnectionManager $connectionManager
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly ConnectionManager $connectionManager,
        private readonly CacheInterface $cache
    ) { }

    /**
     * @param string $indexName
     * @param string $field
     * @return bool
     * @throws \Exception
     */
    public function isFilterable(string $indexName, string $field): bool
    {
        return $this->matches($this->get($indexName)['filterableAttributes'], $field);
    }

    /**
     * @param string $indexName
     * @param string $field
     * @return bool
     * @throws \Exception
     */
    public function isSortable(string $indexName, string $field): bool
    {
        return $this->matches($this->get($indexName)['sortableAttributes'], $field);
    }

    /**
     * @param string $indexName
     * @param string $field
     * @return bool
     * @throws \Exception
     */
    public function isSearchable(string $indexName, string $field): bool
    {
        return $this->matches($this->get($indexName)['searchableAttributes'], $field);
    }

    /**
     * @param string $indexName
     * @return array<string, string[]>
     * @throws \Exception
     */
    private function get(string $indexName): array
    {
        if (isset($this->settings[$indexName])) {
            return $this->settings[$indexName];
        }

        $cacheKey = self::CACHE_KEY_PREFIX . '_' . $indexName;
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            return $this->settings[$indexName] = json_decode($cached, true);
        }

        $all = $this->connectionManager->getConnection()->index($indexName)->getSettings();
        $settings = [];
        foreach (['filterableAttributes', 'sortableAttributes', 'searchableAttributes'] as $key) {
            $settings[$key] = $this->patterns($all[$key] ?? []);
        }
        $this->cache->save(json_encode($settings), $cacheKey, [], self::CACHE_LIFETIME);

        return $this->settings[$indexName] = $settings;
    }

    /**
     * Filterable attributes can be granular objects with their own patterns.
     *
     * @param array $attributes
     * @return string[]
     */
    private function patterns(array $attributes): array
    {
        $patterns = [];
        foreach ($attributes as $attribute) {
            array_push($patterns, ...(is_array($attribute) ? $attribute['attributePatterns'] ?? [] : [$attribute]));
        }

        return $patterns;
    }

    /**
     * @param string[] $patterns
     * @param string $field
     * @return bool
     */
    private function matches(array $patterns, string $field): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === '*' || $pattern === $field || fnmatch($pattern, $field)) {
                return true;
            }
        }

        return false;
    }
}
