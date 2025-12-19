define([
    'Magento_Catalog/js/price-utils',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(priceUtils, viewMode) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    function joinUrl(base, path) {
        if (!path) return '';
        const b = String(base || '').replace(/\/+$/, '');
        const p = String(path || '').replace(/^\/+/, '');
        return b + '/' + p;
    }

    return {
        getImageConfig() {
            return meilisearchConfig.images['category_page_' + viewMode.currentViewMode()];
        },

        getProductImage: function(imagePath) {
            return joinUrl(meilisearchConfig.mediaBaseUrl, imagePath);
        },

        getProductImageByContext: function(hit) {
            const cfg = this.getImageConfig() || {};
            const attr = cfg.type || 'small_image';
            const path = hit && hit[attr];

            if (!path || path === 'no_selection') {
                return '';
            }

            return this.getProductImage(path);
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
