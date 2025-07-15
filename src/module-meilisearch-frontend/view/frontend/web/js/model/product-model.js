define([
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Magento_Catalog/js/price-utils'
], function(configModel, priceUtils) {
    'use strict';

    return {
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
    };
});
