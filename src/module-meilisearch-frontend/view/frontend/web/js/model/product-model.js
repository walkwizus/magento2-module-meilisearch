define([
    'Magento_Catalog/js/price-utils'
], function(priceUtils) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return {
        getProductImage: function(image) {
            return meilisearchConfig.mediaBaseUrl + image;
        },

        getProductUrl: function(urlKey) {
            const suffix = meilisearchConfig.productUrlSuffix;
            const baseUrl = meilisearchConfig.baseUrl;

            if (typeof suffix === 'string' && suffix.trim() !== '') {
                return baseUrl + urlKey + suffix;
            }

            return baseUrl + urlKey;
        },

        formatPrice: function(price) {
            return priceUtils.formatPriceLocale(price, meilisearchConfig.priceFormat, false);
        }
    };
});
