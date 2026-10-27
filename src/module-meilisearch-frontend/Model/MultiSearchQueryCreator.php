<?php

declare(strict_types=1);

namespace Walkwizus\MeilisearchFrontend\Model;

use Meilisearch\Contracts\MultiSearchQuery;
use Meilisearch\Contracts\SearchQuery;

class MultiSearchQueryCreator
{
    /**
     * meilisearch-php 2.0.0-beta.8 moved setIndexUid() from SearchQuery to the new MultiSearchQuery.
     *
     * @return MultiSearchQuery|SearchQuery
     */
    public function create(): MultiSearchQuery|SearchQuery
    {
        return class_exists(MultiSearchQuery::class) ? new MultiSearchQuery() : new SearchQuery();
    }
}
