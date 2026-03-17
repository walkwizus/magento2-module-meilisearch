define([
    'Magento_Catalog/js/price-utils',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function (priceUtils, viewMode) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    function joinUrl(base, path) {
        if (!path) return '';
        const b = String(base || '').replace(/\/+$/, '');
        const p = String(path || '').replace(/^\/+/, '');
        return b + '/' + p;
    }

    function buildCachedImageUrl(imagePath, cfg) {
        const path = String(imagePath || '').replace(/^\/+/, '');
        if (!path) return '';

        const mediaBase = String(meilisearchConfig.mediaBaseUrl || '').replace(/\/+$/, '');
        const hash = cfg && cfg.hash;

        if (hash) {
            return mediaBase + '/cache/' + hash + '/' + path;
        }

        return mediaBase + '/' + path;
    }

    return {
        getImageConfig() {
            return meilisearchConfig.images['category_page_' + viewMode.currentViewMode()];
        },

        getProductImage: function(imagePath) {
            const cfg = this.getImageConfig() || {};
            return buildCachedImageUrl(imagePath, cfg);
        },

        getProductImageByContext: function(hit) {
            const cfg = this.getImageConfig() || {};
            const attr = cfg.type || 'small_image';
            const path = hit && hit[attr];

            if (!path || path === 'no_selection') {
                return '';
            }

            return buildCachedImageUrl(path, cfg);
        },

        getProductUrl: function(urlKey) {
            const baseUrl = meilisearchConfig.baseUrl;
            const path = String(urlKey || '').replace(/^\/+/, '');

            if (!path) {
                return String(baseUrl || '');
            }

            if (/^https?:\/\//i.test(path)) {
                return path;
            }

            return joinUrl(baseUrl, path);
        },

        formatPrice: function(price) {
            return priceUtils.formatPriceLocale(price, meilisearchConfig.priceFormat, false);
        }
    };
});
