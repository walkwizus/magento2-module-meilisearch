define([
    'jquery',
    'ko',
    'mage/cookies'
], function($, ko) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    function postData(url, data) {
        return JSON.stringify({ action: url, data: data });
    }

    return {
        getActions: function(hit, render) {
            const config = meilisearchConfig.productActions || {};
            const addToCartUrl = String(config.addToCartUrl || '');
            const superAttributes = render && render.superAttributes ? render.superAttributes : ko.observableArray([]);

            return {
                isSaleable: !!(hit.stock && hit.stock.is_in_stock),
                addToCartAction: addToCartUrl.replace(/\/?$/, '/') + 'product/' + hit.id + '/',
                productId: hit.id,
                sku: hit.sku,
                formKey: $.mage.cookies.get('form_key') || $('input[name="form_key"]').val() || '',
                superAttributes: superAttributes,
                wishlistPost: config.wishlistEnabled ? ko.pureComputed(() => {
                    const data = { product: hit.id };

                    superAttributes().forEach(attribute => {
                        data['super_attribute[' + attribute.id + ']'] = attribute.value;
                    });

                    return postData(config.addToWishlistUrl, data);
                }) : null,
                comparePost: postData(config.addToCompareUrl, { product: hit.id })
            };
        }
    };
});
