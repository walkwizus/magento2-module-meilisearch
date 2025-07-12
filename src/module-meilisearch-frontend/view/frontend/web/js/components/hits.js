define([
    'uiComponent',
    'ko',
    'Magento_Catalog/js/price-utils',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state'
], function(Component, ko, priceUtils, configModel, viewModeState, searchState) {
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

            return this;
        },

        getProductImage: function(image) {
            return configModel.get('mediaBaseUrl') + image;
        },

        getProductUrl: function(urlKey) {
            const suffix = configModel.get('productUrlSuffix');
            const baseUrl = configModel.get('baseUrl');

            if (typeof suffix === 'string' && suffix.trim() !== '') {
                return baseUrl + urlKey + suffix;
            }

            return baseUrl + urlKey;
        },

        getProductPrice: function(item) {
            if (item[configModel.get('priceAttributeCode')]) {
                return item[configModel.get('priceAttributeCode')];
            }

            return item[price_0];
        },

        formatPrice: function(price) {
            return priceUtils.formatPriceLocale(price, configModel.get('priceFormat'), false);
        }
    });
});
