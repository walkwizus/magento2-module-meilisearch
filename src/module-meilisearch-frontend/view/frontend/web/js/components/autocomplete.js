define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'mage/url'
], function(Component, ko, meilisearchService, configModel, url) {
    'use strict';

    return Component.extend({
        defaults: {
            searchTerm: ko.observable(''),
            results: ko.observable({})
        },

        initialize: function() {
            this._super();
            this.searchService = meilisearchService({
                host: configModel.get('host'),
                apiKey: configModel.get('apiKey'),
                indexName: configModel.get('indexName')
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
