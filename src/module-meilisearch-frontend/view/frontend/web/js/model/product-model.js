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
        const prefix = cfg && cfg.urlPrefix;

        // No prefix means the config provider could not build a resized URL. Render nothing rather
        // than falling back to the original file, which is many times heavier than the resized one.
        if (!path || !prefix) return '';

        return prefix + path + (cfg.urlSuffix || '');
    }

    return {
        getImageConfig() {
            const images = meilisearchConfig.images || {};

            // The view mode is an unvalidated request parameter, so fall back to the grid config
            // rather than leaving the tile with no image config at all.
            return images['category_page_' + viewMode.currentViewMode()] || images.category_page_grid;
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
