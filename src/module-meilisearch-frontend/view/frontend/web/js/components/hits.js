define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/model/product-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state'
], function(Component, productModel, viewModeState, searchState) {
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

            return this;
        }
    });
});
