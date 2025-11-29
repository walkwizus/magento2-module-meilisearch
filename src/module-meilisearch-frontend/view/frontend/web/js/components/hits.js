define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/model/product-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Walkwizus_MeilisearchFrontend/js/service/config-manager',
    'Walkwizus_MeilisearchFrontend/js/fragments'
], function(Component, productModel, viewModeState, searchState, configManager, fragments) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();

            this.currentViewMode = viewModeState.currentViewMode;
            this.isLoading = searchState.isLoading;
            this.searchResults = searchState.searchResults;
            this.totalHits = searchState.totalHits;
            this.hitsPerPage = searchState.hitsPerPage;
            this.currentPage = searchState.currentPage;
            this.searchQuery = searchState.searchQuery;
            this.productModel = productModel;
            this.fragments = configManager.get('fragments', []);
            this.showSwatchesInProductList = configManager.get('showSwatchesInProductList');

            this.afterRenderHit = () => {
                fragments.scheduleInject();
            };

            let ssrEl = document.getElementById('meilisearch-frontend-search-ssr');
            if (ssrEl && ssrEl.parentNode) {
                ssrEl.parentNode.removeChild(ssrEl);
            }

            return this;
        }
    });
});
