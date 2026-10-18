define([
    'ko',
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/model/product-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Walkwizus_MeilisearchFrontend/js/service/product-render',
    'Walkwizus_MeilisearchFrontend/js/fragments'
], function(ko, Component, productModel, viewModeState, searchState, productRender, fragments) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Component.extend({
        defaults: {
            priceTemplate: 'Walkwizus_MeilisearchFrontend/product/price',
            priceAmountTemplate: 'Walkwizus_MeilisearchFrontend/product/price/amount',
            swatchesTemplate: 'Walkwizus_MeilisearchFrontend/product/swatches'
        },

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
            this.fragments = meilisearchConfig.fragments || [];
            this.showSwatchesInProductList = meilisearchConfig.showSwatchesInProductList;
            this.hitRenders = {};

            this.searchResults.subscribe(() => {
                this.hitRenders = {};
            });

            this.afterRenderHit = () => {
                fragments.scheduleInject();
            };

            let ssrEl = document.getElementById('meilisearch-frontend-search-ssr');
            if (ssrEl && ssrEl.parentNode) {
                ssrEl.parentNode.removeChild(ssrEl);
            }

            return this;
        },

        getHitRender: function(hit) {
            const key = String(hit.id);

            if (!this.hitRenders[key]) {
                this.hitRenders[key] = this.createHitRender(hit);
            }

            return this.hitRenders[key];
        },

        createHitRender: function(hit) {
            const selection = ko.observable({});
            const priceAttributeCode = meilisearchConfig.priceAttributeCode;
            const fallbackPrice = hit[priceAttributeCode];

            return {
                attributes: this.showSwatchesInProductList ? productRender.getSwatchAttributes(hit, meilisearchConfig.swatchOptions) : [],

                imageUrl: ko.pureComputed(() => {
                    const imageType = (productModel.getImageConfig() || {}).type || 'small_image';
                    const path = productRender.getSelectedImage(hit, selection(), imageType);

                    return path ? productModel.getProductImage(path) : productModel.getProductImageByContext(hit);
                }),

                productId: hit.id,

                price: ko.pureComputed(() => {
                    const view = productRender.getPriceView(
                        hit,
                        priceAttributeCode,
                        productRender.getSelectedPrice(hit, selection(), priceAttributeCode, meilisearchConfig.currencyCode),
                        meilisearchConfig,
                        amount => productModel.formatPrice(amount)
                    );

                    return view ? Object.assign(view, {
                        amountTemplate: this.priceAmountTemplate,
                        productUrl: productModel.getProductUrl(hit.url_key)
                    }) : null;
                }),

                fallbackPrice: fallbackPrice !== undefined && fallbackPrice !== null
                    && meilisearchConfig.currencyCode === meilisearchConfig.baseCurrencyCode
                    ? productModel.formatPrice(fallbackPrice)
                    : '',

                isSelected: (attribute, option) =>
                    Number(selection()[attribute.attribute_id]) === Number(option.value),

                isAvailable: (attribute, option) =>
                    productRender.isOptionAvailable(hit, selection(), attribute.attribute_id, option.value),

                select: (attribute, option) => {
                    if (!productRender.isOptionAvailable(hit, selection(), attribute.attribute_id, option.value)) {
                        return;
                    }

                    selection(productRender.toggleOption(selection(), attribute.attribute_id, option.value));
                },

                selectedLabel: (attribute) => productRender.getSelectedOptionLabel(attribute, selection()),
                swatchKind: (option) => productRender.getSwatchKind(option),
                swatchStyle: (option) => productRender.getSwatchStyle(option, meilisearchConfig.mediaUrl),
                swatchText: (option) => productRender.getSwatchText(option)
            };
        }
    });
});
