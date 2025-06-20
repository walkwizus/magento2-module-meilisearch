define([
    'jquery',
    'uiComponent',
    'ko',
    'queryBuilder',
    'Walkwizus_MeilisearchMerchandising/js/category/model/merchandising',
    'Magento_Catalog/js/price-utils',
    'jquery/ui'
], function ($, Component, ko, queryBuilder, merchandising, priceUtils) {
    'use strict';

    return Component.extend({
        defaults: {
            qb: null,
            merchandising: merchandising,
            imports: {
                priceFormat: "${ $.provider }:priceFormat",
            }
        },

        initialize: function() {
            this._super();
            this.filters = JSON.parse(this.filters);
            this.initQueryBuilder();

            merchandising.currentCategoryId.subscribe(this.loadRules.bind(this));
            merchandising.docs.subscribe(this.sortPreview.bind(this));

            return this;
        },

        initQueryBuilder: function() {
            let self = this;

            ko.bindingHandlers.initQueryBuilder = {
                init: function (element) {
                    self.qb = $(element).queryBuilder({
                        filters: self.filters
                    });
                }
            };
        },

        loadRules: function(categoryId) {
            let self = this;

            $.ajax(this.ajaxUrl.loadRule, {
                type: 'POST',
                data: {
                    form_key: window.FORM_KEY,
                    category_id: categoryId
                },
                dataType: 'json',
                showLoader: true,
                success: function(r) {
                    if (r.id > 0) {
                        merchandising.currentRule(r);
                        self.qb.queryBuilder('setRules', JSON.parse(r.query));
                    } else {
                        merchandising.currentRule(null);
                        self.qb.queryBuilder('setRules', [{empty: true}]);
                    }
                }
            });
        },

        saveRule: function() {
            let self = this;
            let rules = self.qb.queryBuilder('getRules');

            if (rules) {
                $.ajax(this.ajaxUrl.saveRule, {
                    type: 'POST',
                    data: {
                        form_key: window.FORM_KEY,
                        storeId: self.storeId,
                        categoryId: merchandising.currentCategoryId(),
                        rules: JSON.stringify(rules),
                        docPositions: JSON.stringify(merchandising.docPositions())
                    },
                    dataType: 'json',
                    showLoader: true,
                    success: function(r) {
                        merchandising.message(r.message);
                        setTimeout(function() {
                            $('#messages').addClass('hide');
                            setTimeout(function() {
                                merchandising.message('');
                                $('#messages').removeClass('hide');
                            }, 500);
                        }, 2000);
                    }
                });
            }
        },

        deleteRule: function() {
            $.ajax(this.ajaxUrl.deleteRule, {
                type: 'POST',
                data: {
                    form_key: window.FORM_KEY,
                    categoryId: merchandising.currentCategoryId()
                },
                dataType: 'json',
                showLoader: true,
                success: function(r) {
                    merchandising.message(r.message);
                    setTimeout(function() {
                        $('#messages').addClass('hide');
                        setTimeout(function() {
                            merchandising.message('');
                            $('#messages').removeClass('hide');
                        }, 500);
                    }, 2000);
                }
            });
        },

        preview: function() {
            let self = this;
            let rules = self.qb.queryBuilder('getRules');

            if (rules) {
                $.ajax(this.ajaxUrl.preview, {
                    type: 'POST',
                    data: {
                        form_key: window.FORM_KEY,
                        rules: JSON.stringify(rules),
                        storeId: self.storeId,
                        categoryId: merchandising.currentCategoryId()
                    },
                    dataType: 'json',
                    showLoader: true,
                    success: function(r) {
                        merchandising.docs(r);
                        self.initializePositions(r);
                    }
                });
            }
        },

        initializePositions: function(docs) {
            let docPositions = docs.map(function(doc, index) {
                return {
                    id: doc.id,
                    position: index + 1
                };
            });
            merchandising.docPositions(docPositions);
        },

        sortPreview: function() {
            $('#category-merchandising-preview .product-grid').sortable({
                placeholder: 'sortable-placeholder',
                forcePlaceholderSize: true,
                items: '.product-card',
                update: function(event, ui) {
                    let sortedIds = $(this).children('.product-card').map(function() {
                        return $(this).data('product-id');
                    }).get();

                    let sortedArray = [];
                    let docPositions = [];

                    sortedIds.forEach(function(id, index) {
                        let item = ko.utils.arrayFirst(merchandising.docs(), function(product) {
                            return product.id === id;
                        });
                        if (item) {
                            sortedArray.push(item);
                            docPositions.push({
                                id: id,
                                position: index + 1
                            });
                        }
                    });

                    merchandising.docs(sortedArray);
                    merchandising.docPositions(docPositions);
                }
            });
        },

        copySku: function (item, event) {
            const btn = event.currentTarget;
            const sku = btn.getAttribute('data-sku');
            if (!sku) return;

            navigator.clipboard.writeText(sku).then(() => {
                const tooltip = btn.querySelector('.copy-tooltip');
                btn.classList.add('show-tooltip');

                setTimeout(() => {
                    btn.classList.remove('show-tooltip');
                }, 1500);
            });
        },

        formatPrice: function(price) {
            return priceUtils.formatPriceLocale(price, this.priceFormat, false);
        }
    });
});
