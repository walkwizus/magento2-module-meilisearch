define([
    'meilisearch'
], function(meilisearch) {
    'use strict';

    return function(config) {
        const client = new meilisearch.Meilisearch({
            host: config.host,
            apiKey: config.apiKey
        });

        const index = client.index(config.indexName);

        return {
            search: function(query, params) {
                return index.search(query, params);
            },
            multiSearch: function(queries) {
                return client.multiSearch({
                    queries: queries
                });
            }
        };
    };
});
