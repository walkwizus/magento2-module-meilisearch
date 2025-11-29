define([
    'Walkwizus_MeilisearchFrontend/js/service/config-manager',
    'Magento_Catalog/js/price-utils'
], function(configManager, priceUtils) {
    'use strict';

    return {
        getProductImage: function(image) {
            return configManager.get('mediaBaseUrl') + image;
        },

        getProductUrl: function(urlKey) {
            const suffix = configManager.get('productUrlSuffix');
            const baseUrl = configManager.get('baseUrl');

            if (typeof suffix === 'string' && suffix.trim() !== '') {
                return baseUrl + urlKey + suffix;
            }

            return baseUrl + urlKey;
        },

        formatPrice: function(price) {
            return priceUtils.formatPriceLocale(price, configManager.get('priceFormat'), false);
        }
    };
});
