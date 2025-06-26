define([
    'uiComponent',
    'ko',
    'Magento_Catalog/js/price-utils'
], function(Component, ko, priceUtils) {
    'use strict';

    return Component.extend({
        defaults: {
            searchResults: ko.observable({}),
            currentViewMode: ko.observable(),
            imports: {
                baseUrl: "${ $.provider }:baseUrl",
                productUrlSuffix: "${ $.provider }:productUrlSuffix",
                productUseCategories: "${ $.provider }:productUseCategories",
                mediaBaseUrl: "${ $.provider }:mediaBaseUrl"
            }
        },

        initialize: function() {
            this._super();
            return this;
        },

        getProductImage: function(image) {
            return this.mediaBaseUrl + image;
        },

        getProductUrl: function(urlKey) {
            const suffix = this.productUrlSuffix;
            const baseUrl = this.baseUrl;

            if (typeof suffix === 'string' && suffix.trim() !== '') {
                return baseUrl + urlKey + suffix;
            }

            return baseUrl + urlKey;
        },

        getProductPrice: function(item) {
            if (item[this.priceAttributeCode]) {
                return item[this.priceAttributeCode];
            }

            return item[price_0];
        },

        formatPrice: function(price) {
            return priceUtils.formatPriceLocale(price, this.priceFormat, false);
        }
    });
});
