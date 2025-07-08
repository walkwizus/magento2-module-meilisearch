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
            results: ko.observable({}),
            isActive: ko.observable(false)
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

            this._bindClickOutside();

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
