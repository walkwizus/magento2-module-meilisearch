define([
    'uiComponent',
    'ko',
    'jquery',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'mage/url'
], function(Component, ko, $, meilisearchService, configModel, url) {
    'use strict';

    return Component.extend({
        defaults: {
            searchTerm: ko.observable(''),
            results: ko.observableArray(),
            isActive: ko.observable(false)
        },

        initialize: function() {
            this._super();

            this.baseUrl = configModel.get('baseUrl');

            this.searchService = meilisearchService({
                host: configModel.get('host'),
                apiKey: configModel.get('apiKey')
            });

            this.searchTerm.subscribe((v) => {
                this.performSearch(v);
            });

            this.hasResults = ko.pureComputed(() => {
                const resultData = this.results();
                return Object.values(resultData).some(list => Array.isArray(list) && list.length > 0);
            });

            this._bindClickOutside();

            return this;
        },

        performSearch: function(terms) {
            if (!terms) {
                this.results({});
                return;
            }

            const indexMap = configModel.get('autocompleteIndex');
            const queries = Object.entries(indexMap).map(([_, indexUid]) => ({
                indexUid,
                q: terms,
                limit: 5
            }));

            this.searchService.multiSearch(queries).then((res) => {
                const mappedResults = {};

                res.results.forEach(result => {
                    const alias = Object.keys(indexMap).find(key => indexMap[key] === result.indexUid);
                    if (alias) {
                        mappedResults[alias] = result.hits;
                    }
                });

                this.results(mappedResults);
            });
        },

        getSearchUrl: function() {
            return url.build('catalogsearch/result');
        },

        toggleSearch: function(el) {
            this.isActive(!this.isActive());
            if (this.isActive()) {
                $('#search').focus();
            }
        },

        _bindClickOutside: function() {
            const self = this;
            $(document).on('click.miniSearch', (e) => {
                const $target = $(e.target);
                const $container = $('.block-search');

                if (!$container.is($target) && $container.has($target).length === 0) {
                    self.isActive(false);
                    self.results([]);
                }
            });
        }
    });
});
