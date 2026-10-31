define([
    'ko',
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/model/product-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Walkwizus_MeilisearchFrontend/js/service/product-render',
    'Walkwizus_MeilisearchFrontend/js/fragments',
    'Magento_Ui/js/lib/knockout/template/engine'
], function(ko, Component, productModel, viewModeState, searchState, productRender, fragments, templateEngine) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;
    const SWAP_TIMEOUT = 5000;

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

                if (!searchState.isInitializing()) {
                    this.replaceSsr();
                }
            });

            this.afterRenderHit = () => {
                fragments.scheduleInject();
            };

            // The server-rendered list stays on screen until this one, its toolbars and its nested templates
            // have all rendered, then the two swap in one go: no blank frame, no footer jumping up and back.
            // The render counter is page-wide and never settles if any template fails to load, so after a
            // timeout the swap also happens once this list shows what the search returned. The container is
            // hidden here, not in the markup, and only next to a server-rendered list, so a theme still
            // overriding search.phtml or an older hits.js keeps working.
            const container = document.getElementById('meilisearch-frontend-search');
            if (container && document.getElementById('meilisearch-frontend-search-ssr')) {
                container.hidden = true;
            }
            const swapWhenRendered = () => {
                if (!document.getElementById('meilisearch-frontend-search-ssr')) {
                    return;
                }
                if (this.hasRendered()) {
                    this.replaceSsr();
                } else {
                    setTimeout(swapWhenRendered, 500);
                }
            };
            setTimeout(swapWhenRendered, SWAP_TIMEOUT);

            this.afterRenderTemplate = () => {
                this.whenFullyRendered().then(() => this.replaceSsr());
            };

            return this;
        },

        whenFullyRendered: function() {
            return templateEngine.waitForFinishRender().then(() =>
                searchState.isInitializing() || this.elems().length < (this.initChildCount || 0)
                    ? this.whenFullyRendered()
                    : null
            );
        },

        hasRendered: function() {
            const container = document.getElementById('meilisearch-frontend-search');

            if (!container || searchState.isInitializing()) {
                return false;
            }

            return this.totalHits() > 0
                ? container.querySelectorAll('li.product-item').length === (this.searchResults().hits || []).length
                : !!container.querySelector('.message.empty');
        },

        replaceSsr: function() {
            const ssrEl = document.getElementById('meilisearch-frontend-search-ssr');
            const container = document.getElementById('meilisearch-frontend-search');

            if (ssrEl) {
                ssrEl.remove();
            }

            if (container) {
                container.hidden = false;
            }
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
