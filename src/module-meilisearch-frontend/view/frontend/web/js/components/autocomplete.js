define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'mage/url'
], function(Component, ko, meilisearchService, url) {
    'use strict';

    return Component.extend({
        defaults: {
            searchTerm: ko.observable(''),
            results: ko.observable({})
        },

        initialize: function() {
            this._super();

            this.searchService = meilisearchService({
                host: this.host,
                apiKey: this.apiKey,
                indexName: this.indexName
            });

            this.searchTerm.subscribe((v) => {
                this.performSearch(v);
            });

            return this;
        },

        performSearch: function(terms) {
            if (!terms) {
                this.results([]);
                return;
            }
            this.searchService.search(terms)
                .then((res) => {
                    this.results(res.hits);
                });
        },

        getSearchUrl: function() {
            return url.build('catalogsearch/result');
        }
    });
});
