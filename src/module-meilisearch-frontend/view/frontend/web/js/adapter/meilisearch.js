define([
    'Walkwizus_MeilisearchFrontend/js/lib/meilisearch.umd'
], function(meilisearchNamespace) {
    'use strict';

    if (typeof window.MeiliSearch !== 'undefined') {
        return window.MeiliSearch;
    }

    return meilisearchNamespace.MeiliSearch;
});
