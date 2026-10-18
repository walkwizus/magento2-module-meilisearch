define([
    'jquery',
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchMerchandising/js/category/model/merchandising',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'Walkwizus_MeilisearchMerchandising/js/category/query-builder/templates',
    'sortablejs',
    'queryBuilder'
], function ($, Component, ko, merchandising, priceUtils, confirm, $t, templates, Sortable) {
    'use strict';

    const VIEW_MODE_STORAGE_KEY = 'meilisearch_merchandising_view_mode';

    return Component.extend({
        defaults: {
            qb: null,
            merchandising: merchandising,
            pageSizes: [20, 50, 100, 200],
            pageSize: 20,
            page: 1,
            total: 0,
            pageableCount: 0,
            sortableCount: 0,
            truncated: false,
            previewLoaded: false,
            searchQuery: '',
            appliedQuery: '',
            viewMode: 'grid',
            moves: [],
            pins: {},
            buried: [],
            sortBy: 'position',
            savedArrangement: '',
            ruleOpened: true,
            productsOpened: true,
            message: null,
            messageTimeout: null,
            imports: {
                priceFormat: '${ $.provider }:priceFormat'
            }
        },

        initialize: function() {
            this._super();
            this.filters = JSON.parse(this.filters);
            this.sortByOptions = [
                {value: 'position', label: $t('Manual order')},
                {value: 'newest', label: $t('Newest first')},
                {value: 'name', label: $t('Name')},
                {value: 'price_asc', label: $t('Price: low to high')},
                {value: 'price_desc', label: $t('Price: high to low')},
                {value: 'stock', label: $t('Stock')}
            ];
            this.initBindings();

            this.pages = ko.pureComputed(function() {
                return Math.max(1, Math.ceil(this.pageableCount() / this.pageSize()));
            }, this);
            this.offset = ko.pureComputed(function() {
                return (this.page() - 1) * this.pageSize();
            }, this);
            this.isSearching = ko.pureComputed(function() {
                return this.appliedQuery() !== '';
            }, this);
            this.isFiltered = ko.pureComputed(function() {
                return this.isSearching() || Object.keys(merchandising.facetFilters()).length > 0;
            }, this);
            this.canArrange = ko.pureComputed(function() {
                return this.previewLoaded();
            }, this);
            this.isAutoSort = ko.pureComputed(function() {
                return this.sortBy() !== 'position';
            }, this);
            this.isArrangementDirty = ko.pureComputed(function() {
                return this.previewLoaded() && this.serializeArrangement() !== this.savedArrangement();
            }, this);
            this.canSort = ko.pureComputed(function() {
                return this.canArrange() && !this.isFiltered();
            }, this);

            this.isArrangementDirty.subscribe(function(dirty) {
                merchandising.hasUnsavedChanges(dirty);
            });
            this.sortBy.subscribe(function() {
                if (this.previewLoaded() && !this.applyingSaved) {
                    this.page(1);
                    this.loadPreview();
                }
            }, this);
            this.initViewMode();
            this.pageSize.subscribe(this.onPageSizeChange.bind(this));
            merchandising.currentCategoryId.subscribe(this.onCategoryChange.bind(this));
            merchandising.facetFilters.subscribe(function() {
                if (this.previewLoaded()) {
                    this.page(1);
                    this.loadPreview();
                }
            }, this);

            return this;
        },

        initObservable: function() {
            this._super().observe([
                'pageSize', 'page', 'total', 'pageableCount', 'sortableCount', 'truncated', 'previewLoaded',
                'searchQuery', 'appliedQuery', 'viewMode', 'moves', 'pins', 'buried', 'sortBy', 'savedArrangement', 'ruleOpened', 'productsOpened', 'message'
            ]);

            return this;
        },

        initBindings: function() {
            let self = this;

            ko.bindingHandlers.initQueryBuilder = {
                init: function(element) {
                    self.qb = $(element).on('afterCreateRuleInput.queryBuilder', function(event, rule) {
                        rule.$el.find('.rule-value-container input[type=text], .rule-value-container input[type=number]')
                            .addClass('admin__control-text');
                        rule.$el.find('.rule-value-container textarea').addClass('admin__control-textarea');
                    }).queryBuilder({
                        filters: self.filters,
                        templates: templates,
                        select_placeholder: $t('Select an attribute'),
                        inputs_separator: '<span class="merch-qb-separator">' + $t('and') + '</span>',
                        lang: self.getQueryBuilderLang()
                    });
                }
            };

            ko.bindingHandlers.merchandisingSortable = {
                init: function(element) {
                    let sortable = Sortable.create(element, {
                        draggable: '.merch-product',
                        filter: 'input, button, a, select, label',
                        preventOnFilter: false,
                        animation: 150,
                        forceFallback: true,
                        fallbackOnBody: true,
                        fallbackTolerance: 4,
                        ghostClass: 'merch-product-ghost',
                        chosenClass: 'merch-product-chosen',
                        dragClass: 'merch-product-drag',
                        onEnd: function(event) {
                            if (event.oldDraggableIndex !== event.newDraggableIndex) {
                                self.onDrop(event.item.getAttribute('data-product-id'), event.newDraggableIndex);
                            }
                        }
                    });

                    ko.utils.domData.set(element, 'merchandisingSortable', sortable);
                    ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
                        sortable.destroy();
                    });
                },
                update: function(element, valueAccessor) {
                    ko.utils.domData.get(element, 'merchandisingSortable').option('disabled', !ko.unwrap(valueAccessor()));
                }
            };
        },

        initViewMode: function() {
            try {
                this.viewMode(window.localStorage.getItem(VIEW_MODE_STORAGE_KEY) === 'list' ? 'list' : 'grid');
            } catch (e) {
                this.viewMode('grid');
            }

            this.viewMode.subscribe(function(mode) {
                try {
                    window.localStorage.setItem(VIEW_MODE_STORAGE_KEY, mode);
                } catch (e) {
                    return;
                }
            });
        },

        setGridView: function() {
            this.viewMode('grid');
        },

        setListView: function() {
            this.viewMode('list');
        },

        getQueryBuilderLang: function() {
            return {
                add_rule: $t('Add Condition'),
                add_group: $t('Add Group'),
                delete_rule: $t('Delete'),
                delete_group: $t('Delete Group'),
                conditions: {
                    AND: $t('all'),
                    OR: $t('any')
                },
                operators: {
                    equal: $t('is'),
                    not_equal: $t('is not'),
                    in: $t('is one of'),
                    not_in: $t('is not one of'),
                    less: $t('less than'),
                    less_or_equal: $t('equals or less than'),
                    greater: $t('greater than'),
                    greater_or_equal: $t('equals or greater than'),
                    between: $t('between'),
                    is_null: $t('is undefined'),
                    is_not_null: $t('is defined')
                },
                errors: {
                    no_filter: $t('Select an attribute.'),
                    empty_group: $t('Add at least one condition.'),
                    radio_empty: $t('Select a value.'),
                    checkbox_empty: $t('Select a value.'),
                    select_empty: $t('Select a value.'),
                    string_empty: $t('Enter a value.'),
                    number_nan: $t('Enter a valid number.'),
                    number_between_invalid: $t('Invalid values, {0} is greater than {1}.')
                }
            };
        },

        onCategoryChange: function(categoryId) {
            this.previewLoaded(false);
            merchandising.facetFilters({});
            this.moves([]);
            this.page(1);
            this.searchQuery('');
            this.appliedQuery('');
            this.total(0);
            this.clearMessage();
            merchandising.docs([]);

            if (categoryId) {
                this.loadRule(categoryId);
            }
        },

        loadRule: function(categoryId) {
            let self = this;

            this.request(this.ajaxUrl.loadRule, {category_id: categoryId}).done(function(r) {
                if (r.id > 0) {
                    merchandising.currentRule(r);
                    self.qb.queryBuilder('setRules', JSON.parse(r.query));
                } else {
                    merchandising.currentRule(null);
                    self.qb.queryBuilder('reset');
                }

                self.loadPreview(false, true);
            });
        },

        getRules: function() {
            let rules = this.qb.queryBuilder('getRules');

            if (!rules) {
                this.ruleOpened(true);
            }

            return rules;
        },

        preview: function() {
            if (!this.getRules()) {
                return;
            }

            this.page(1);
            this.loadPreview();
        },

        getPreviewRules: function() {
            return this.qb ? this.qb.queryBuilder('getRules', {allow_invalid: true}) : null;
        },

        hasRuleConditions: function(rules) {
            let hasCondition = function(group) {
                return (group.rules || []).some(function(rule) {
                    return rule.rules ? hasCondition(rule) : !!rule.id;
                });
            };

            return !!rules && hasCondition(rules);
        },

        getArrangement: function() {
            return {
                moves: this.moves(),
                pins: this.pins(),
                buried: this.buried(),
                sortBy: this.sortBy()
            };
        },

        serializeArrangement: function() {
            let pins = this.pins();

            return JSON.stringify({
                moves: this.moves(),
                pins: Object.keys(pins).sort().map(function(id) {
                    return [id, pins[id]];
                }),
                buried: this.buried(),
                sortBy: this.sortBy()
            });
        },

        applySavedArrangement: function(saved) {
            this.moves([]);
            this.pins($.extend({}, saved.pins || {}));
            this.buried((saved.buried || []).map(Number));
            this.sortBy(saved.sortBy || 'position');
            this.savedArrangement(this.serializeArrangement());
        },

        loadPreview: function(scrollToProducts, withSaved) {
            let self = this;
            let rules = this.getPreviewRules();

            if (this.previewRequest) {
                this.previewRequest.abort();
            }

            this.previewRequest = this.request(this.ajaxUrl.preview, {
                rules: rules ? JSON.stringify(rules) : '',
                filters: JSON.stringify(merchandising.facetFilters()),
                storeId: this.storeId,
                categoryId: merchandising.currentCategoryId(),
                arrangement: JSON.stringify(this.getArrangement()),
                withSaved: withSaved ? 1 : 0,
                page: this.page(),
                pageSize: this.pageSize(),
                query: this.appliedQuery()
            }).done(function(r) {
                if (!r.success) {
                    self.showMessage('error', r.message);
                    return;
                }

                if (r.saved) {
                    self.applyingSaved = true;
                    self.applySavedArrangement(r.saved);
                    self.applyingSaved = false;
                }

                merchandising.docs(r.items);
                self.total(r.total);
                self.pageableCount(r.pageable);
                self.sortableCount(r.sortable);
                self.truncated(r.truncated);
                self.previewLoaded(true);
                self.productsOpened(true);

                if (scrollToProducts) {
                    self.scrollToProducts();
                }
            }).always(function() {
                self.previewRequest = null;
            });
        },

        scrollToProducts: function() {
            let toolbar = $('.merch-toolbar').closest('.merch-section');
            let header = $('.page-actions').first();
            let offset = (header.length ? Math.max(header[0].getBoundingClientRect().bottom, header.outerHeight()) : 0) + 20;

            if (toolbar.length && toolbar.offset().top - offset < $(window).scrollTop()) {
                $('html, body').scrollTop(toolbar.offset().top - offset);
            }
        },

        saveRule: function() {
            let self = this;
            let previewRules = this.getPreviewRules();
            let categoryId = merchandising.currentCategoryId();
            let data = {storeId: this.storeId, categoryId: categoryId};
            let rules = null;

            if (!categoryId) {
                return;
            }

            if (this.hasRuleConditions(previewRules)) {
                rules = this.getRules();

                if (!rules) {
                    return;
                }

                data.rules = JSON.stringify(rules);
            }

            if (this.isArrangementDirty()) {
                data.arrangement = JSON.stringify(this.getArrangement());
            }

            if (!data.rules && !data.arrangement) {
                this.showMessage('success', $t('There is nothing to save.'));
                return;
            }

            this.request(this.ajaxUrl.saveRule, data).done(function(r) {
                if (!r.success) {
                    self.showMessage('error', r.message);
                    return;
                }

                if (rules) {
                    merchandising.currentRule({category_id: categoryId});

                    if (merchandising.ruleCategoryIds.indexOf(String(categoryId)) === -1) {
                        merchandising.ruleCategoryIds.push(String(categoryId));
                    }
                }

                self.moves([]);
                self.savedArrangement(self.serializeArrangement());
                self.showMessage('success', r.message);
            });
        },

        deleteRule: function() {
            let self = this;
            let categoryId = merchandising.currentCategoryId();

            confirm({
                title: $t('Delete Rule'),
                content: $t('Are you sure you want to delete the rule of "%1"?').replace('%1', merchandising.currentCategoryName()),
                actions: {
                    confirm: function() {
                        self.request(self.ajaxUrl.deleteRule, {categoryId: categoryId}).done(function(r) {
                            if (!r.success) {
                                self.showMessage('error', r.message);
                                return;
                            }

                            merchandising.currentRule(null);
                            merchandising.ruleCategoryIds.remove(String(categoryId));
                            self.qb.queryBuilder('reset');
                            self.page(1);
                            self.loadPreview(false, true);
                            self.showMessage('success', r.message);
                        });
                    }
                }
            });
        },

        resetPositions: function() {
            this.page(1);
            this.loadPreview(false, true);
        },

        onDrop: function(productId, index) {
            let docs = merchandising.docs().slice();
            let currentIndex = docs.findIndex(function(doc) {
                return String(doc.id) === String(productId);
            });

            if (currentIndex === -1 || currentIndex === index) {
                return;
            }

            let product = docs.splice(currentIndex, 1)[0];

            if (this.isAutoSort()) {
                this.pinAt(product, this.offset() + index + 1);
                return;
            }

            docs.splice(index, 0, product);
            merchandising.docs([]);
            merchandising.docs(docs);
            this.moves.push({id: product.id, position: this.offset() + index + 1});
        },

        pinAt: function(product, position) {
            let pins = $.extend({}, this.pins());

            pins[product.id] = position;
            this.buried.remove(Number(product.id));
            this.pins(pins);
            this.loadPreview();
        },

        isPinned: function(product) {
            return this.pins()[product.id] !== undefined;
        },

        isBuried: function(product) {
            return this.buried().indexOf(Number(product.id)) !== -1;
        },

        togglePin: function(product) {
            let pins = $.extend({}, this.pins());
            let position = this.getPosition(product);

            if (this.isPinned(product)) {
                delete pins[product.id];

                if (!this.isAutoSort() && position) {
                    this.moves.push({id: product.id, position: position});
                }

                this.pins(pins);
                this.loadPreview();
                return;
            }

            if (position) {
                this.pinAt(product, position);
            }
        },

        toggleBury: function(product) {
            let pins = $.extend({}, this.pins());
            let id = Number(product.id);

            if (this.isBuried(product)) {
                this.buried.remove(id);
            } else {
                delete pins[product.id];
                this.pins(pins);
                this.buried.push(id);
            }

            this.loadPreview();
        },

        moveTo: function(product, position) {
            position = parseInt(position, 10);

            if (!position || position < 1 || position === this.getPosition(product)) {
                return;
            }

            position = Math.min(position, this.sortableCount());

            if (this.isAutoSort() || this.isPinned(product)) {
                this.pinAt(product, position);
                return;
            }

            this.moves.push({id: product.id, position: position});
            this.loadPreview();
        },

        onPositionChange: function(product, event) {
            this.moveTo(product, event.target.value);
        },

        onPositionKeyDown: function(product, event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                event.target.blur();

                return false;
            }

            return true;
        },

        moveToTop: function(product) {
            this.moveTo(product, 1);
        },


        getPosition: function(product) {
            if (this.isFiltered()) {
                return product.merchandising_position;
            }

            return this.offset() + merchandising.docs.indexOf(product) + 1;
        },

        search: function() {
            this.appliedQuery(this.searchQuery().trim());
            this.page(1);
            this.loadPreview();
        },

        clearSearch: function() {
            this.searchQuery('');
            this.search();
        },

        goToPage: function(page) {
            page = Math.min(Math.max(1, parseInt(page, 10) || 1), this.pages());

            if (page !== this.page()) {
                this.page(page);
                this.loadPreview(true);
            }
        },

        previousPage: function() {
            this.goToPage(this.page() - 1);
        },

        nextPage: function() {
            this.goToPage(this.page() + 1);
        },

        onPageInputChange: function(data, event) {
            this.goToPage(event.target.value);
            event.target.value = this.page();
        },

        onPageSizeChange: function() {
            this.page(1);

            if (this.previewLoaded()) {
                this.loadPreview(true);
            }
        },

        toggleRule: function() {
            this.ruleOpened(!this.ruleOpened());
        },

        toggleProducts: function() {
            this.productsOpened(!this.productsOpened());
        },

        showMessage: function(type, text) {
            let self = this;

            clearTimeout(this.messageTimeout);
            this.message({type: type, text: text});

            if (type === 'success') {
                this.messageTimeout = setTimeout(function() {
                    self.clearMessage();
                }, 5000);
            }
        },

        clearMessage: function() {
            clearTimeout(this.messageTimeout);
            this.message(null);
        },

        request: function(url, data) {
            let self = this;

            return $.ajax(url, {
                type: 'POST',
                data: $.extend({form_key: window.FORM_KEY}, data),
                dataType: 'json',
                showLoader: true
            }).fail(function(xhr, status) {
                if (status !== 'abort') {
                    self.showMessage('error', $t('Something went wrong while processing the request.'));
                }
            });
        },

        getImageUrl: function(product) {
            let image = product.small_image || product.thumbnail || product.image;

            if (!image || image === 'no_selection') {
                return null;
            }

            return this.productMediaUrl + image;
        },

        getEditUrl: function(product) {
            return this.productEditUrl.replace('__id__', product.id);
        },

        getQty: function(product) {
            return product.stock && product.stock.qty !== undefined ? product.stock.qty : null;
        },

        isInStock: function(product) {
            return !product.stock || product.stock.is_in_stock !== false;
        },

        copySku: function(product, event) {
            let button = event.currentTarget;

            navigator.clipboard.writeText(product.sku).then(function() {
                button.classList.add('_copied');
                setTimeout(function() {
                    button.classList.remove('_copied');
                }, 1500);
            });
        },

        formatPrice: function(price) {
            return price === undefined || price === null ? '' : priceUtils.formatPriceLocale(price, this.priceFormat, false);
        }
    });
});
