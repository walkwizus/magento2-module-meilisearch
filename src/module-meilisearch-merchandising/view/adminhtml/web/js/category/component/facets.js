define([
    'jquery',
    'uiComponent',
    'ko',
    'uiRegistry',
    'Walkwizus_MeilisearchMerchandising/js/category/model/merchandising',
    'Walkwizus_MeilisearchFrontend/js/service/facets',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'sortablejs',
    'jstree'
], function ($, Component, ko, registry, merchandising, facetService, priceUtils, confirm, modal, $t, Sortable) {
    'use strict';

    const QUERY_BUILDER = 'meilisearch-merchandising-query-builder';
    const CATEGORY_TREE = 'meilisearch-merchandising-category-tree';
    const BOOLEAN_SETTINGS = ['showMore', 'searchable', 'searchboxFuzzyEnabled', 'hideIfNonDiscriminant'];

    return Component.extend({
        defaults: {
            template: 'Walkwizus_MeilisearchMerchandising/category/facets',
            merchandising: merchandising,
            loaded: false,
            isAnchor: true,
            facets: [],
            facetOrder: [],
            hiddenFacets: [],
            settingsFacet: null,
            collapsedFacets: [],
            expandedValues: [],
            valueSearches: {},
            hasOwnConfig: false,
            distribution: {},
            stats: {},
            totalHits: 0,
            snapshot: '',
            stores: [],
            copyStoreIds: [],
            copyIncludeChildren: true,
            copyOverwrite: false
        },

        initialize: function() {
            this._super();
            this.valueStates = {};
            this.settingStates = {};
            this.sortOptions = [
                {value: 'default', label: $t('Use global setting')},
                {value: 'count', label: $t('Product count')},
                {value: 'alpha', label: $t('Alphabetical')},
                {value: 'hidden', label: $t('Pinned values only')}
            ];
            this.booleanOptions = [
                {value: '', label: $t('Use global setting')},
                {value: '1', label: $t('Yes')},
                {value: '0', label: $t('No')}
            ];
            this.initBindings();

            this.orderedFacets = ko.pureComputed(function() {
                let facets = this.facets();

                return this.facetOrder().map(function(code) {
                    return facets.find(function(facet) {
                        return facet.code === code;
                    });
                }).filter(Boolean);
            }, this);
            this.facetItems = {};
            this.renderedFacets = ko.pureComputed(function() {
                return this.orderedFacets().map(this.getFacetItem, this);
            }, this);
            this.activeFilters = ko.pureComputed(this.getActiveFilters, this);
            this.isDirty = ko.pureComputed(function() {
                return this.loaded() && this.serialize() !== this.snapshot();
            }, this);
            this.isDirty.subscribe(function(dirty) {
                merchandising.facetsDirty(dirty);
            });

            merchandising.currentCategoryId.subscribe(this.onCategoryChange.bind(this));
            merchandising.currentRule.subscribe(this.refreshCounts.bind(this));
            merchandising.facetFilters.subscribe(this.refreshCounts.bind(this));

            return this;
        },

        initObservable: function() {
            this._super().observe([
                'loaded', 'isAnchor', 'facets', 'facetOrder', 'hiddenFacets', 'settingsFacet', 'collapsedFacets',
                'expandedValues', 'valueSearches', 'hasOwnConfig', 'distribution', 'stats', 'totalHits', 'snapshot',
                'copyStoreIds', 'copyIncludeChildren', 'copyOverwrite'
            ]);

            return this;
        },

        initBindings: function() {
            ko.bindingHandlers.merchandisingFacetSortable = {
                init: function(element, valueAccessor) {
                    let options = valueAccessor();
                    let nextSibling = null;
                    let sortable = Sortable.create(element, {
                        draggable: options.draggable,
                        handle: options.handle,
                        animation: 150,
                        forceFallback: true,
                        fallbackOnBody: true,
                        fallbackTolerance: 4,
                        ghostClass: 'merch-facet-ghost',
                        onStart: function(event) {
                            nextSibling = event.item.nextSibling;
                        },
                        onEnd: function(event) {
                            event.from.insertBefore(event.item, nextSibling);
                            if (event.oldDraggableIndex !== event.newDraggableIndex) {
                                options.onSort(event.oldDraggableIndex, event.newDraggableIndex);
                            }
                        }
                    });

                    ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
                        sortable.destroy();
                    });
                }
            };
        },

        onCategoryChange: function(categoryId) {
            this.loaded(false);
            this.settingsFacet(null);
            this.expandedValues([]);
            this.collapsedFacets([]);
            this.valueSearches({});
            this.distribution({});
            merchandising.facetsDirty(false);

            if (categoryId) {
                this.loadState();
            }
        },

        loadState: function() {
            let self = this;

            this.request(this.ajaxUrl.getFacets, {}).done(function(r) {
                if (r.success) {
                    self.applyState(r);
                    self.loadCounts();
                }
            });
        },

        applyState: function(state) {
            let ordering = state.ordering || {};
            let codes = state.facets.map(function(facet) {
                return facet.code;
            });
            let pinned = ((ordering.facets || {}).order || []).filter(function(code) {
                return codes.indexOf(code) !== -1;
            });

            this.valueStates = {};
            this.settingStates = {};
            this.facets(state.facets);
            this.isAnchor(state.isAnchor !== false);
            this.facetOrder(pinned.concat(codes.filter(function(code) {
                return pinned.indexOf(code) === -1;
            })));
            this.hiddenFacets(((ordering.facets || {}).hidden || []).slice());

            state.facets.forEach(function(facet) {
                let valueOrdering = (ordering.values || {})[facet.code] || {};
                let settings = (ordering.settings || {})[facet.code] || {};
                let valueState = this.getValueState(facet.code);
                let settingState = this.getSettingState(facet.code);

                valueState.order((valueOrdering.order || []).slice());
                valueState.hidden((valueOrdering.hidden || []).slice());
                valueState.sortRemainingBy(valueOrdering.sortRemainingBy || 'default');

                BOOLEAN_SETTINGS.forEach(function(setting) {
                    settingState[setting](settings[setting] === undefined ? '' : (settings[setting] ? '1' : '0'));
                });
                settingState.showMoreLimit(settings.showMoreLimit ? String(settings.showMoreLimit) : '');
            }, this);

            this.hasOwnConfig(!!state.ordering);
            this.snapshot(this.serialize());
            this.loaded(true);
        },

        refreshCounts: function() {
            if (this.loaded()) {
                this.loadCounts();
            }
        },

        loadCounts: function() {
            let self = this;
            let rules = this.getCurrentRules();

            if (this.countsRequest) {
                this.countsRequest.abort();
            }

            this.countsRequest = this.request(this.ajaxUrl.previewFacets, {
                rules: rules ? JSON.stringify(rules) : '',
                filters: JSON.stringify(merchandising.facetFilters())
            }, false).done(function(r) {
                if (r.success) {
                    self.distribution(r.facetDistribution || {});
                    self.stats(r.facetStats || {});
                    self.totalHits(r.totalHits || 0);
                }
            }).always(function() {
                self.countsRequest = null;
            });
        },

        getCurrentRules: function() {
            let queryBuilder = registry.get(QUERY_BUILDER);

            if (!merchandising.currentRule() || !queryBuilder || !queryBuilder.qb) {
                return null;
            }

            return queryBuilder.qb.queryBuilder('getRules', {allow_invalid: true});
        },

        getValueState: function(code) {
            if (!this.valueStates[code]) {
                this.valueStates[code] = {
                    order: ko.observableArray([]),
                    hidden: ko.observableArray([]),
                    sortRemainingBy: ko.observable('default')
                };
            }

            return this.valueStates[code];
        },

        getSettingState: function(code) {
            if (!this.settingStates[code]) {
                this.settingStates[code] = {showMoreLimit: ko.observable('')};

                BOOLEAN_SETTINGS.forEach(function(setting) {
                    this.settingStates[code][setting] = ko.observable('');
                }, this);
            }

            return this.settingStates[code];
        },

        getEffectiveConfig: function(facet) {
            let settingState = this.getSettingState(facet.code);
            let config = $.extend({}, facet);

            BOOLEAN_SETTINGS.forEach(function(setting) {
                if (settingState[setting]() !== '') {
                    config[setting] = settingState[setting]() === '1';
                }
            });

            if (parseInt(settingState.showMoreLimit(), 10) > 0) {
                config.showMoreLimit = parseInt(settingState.showMoreLimit(), 10);
            }

            return config;
        },

        serialize: function() {
            let values = {};
            let settings = {};

            this.facetOrder();
            this.hiddenFacets();

            $.each(this.valueStates, function(code, state) {
                if (state.order().length || state.hidden().length || state.sortRemainingBy() !== 'default') {
                    values[code] = {order: state.order(), hidden: state.hidden(), sortRemainingBy: state.sortRemainingBy()};
                }
            });

            $.each(this.settingStates, function(code, state) {
                let facetSettings = {};

                BOOLEAN_SETTINGS.forEach(function(setting) {
                    if (state[setting]() !== '') {
                        facetSettings[setting] = state[setting]() === '1';
                    }
                });

                if (parseInt(state.showMoreLimit(), 10) > 0) {
                    facetSettings.showMoreLimit = parseInt(state.showMoreLimit(), 10);
                }

                if (Object.keys(facetSettings).length) {
                    settings[code] = facetSettings;
                }
            });

            return JSON.stringify({
                facets: {order: this.facetOrder(), hidden: this.hiddenFacets()},
                values: values,
                settings: settings
            });
        },

        getFacetItem: function(facet) {
            if (!this.facetItems[facet.code] || this.facetItems[facet.code].facet !== facet) {
                this.facetItems[facet.code] = {
                    facet: facet,
                    code: facet.code,
                    label: facet.label,
                    view: ko.pureComputed(this.buildFacetView.bind(this, facet))
                };
            }

            return this.facetItems[facet.code];
        },

        buildFacetView: function(facet) {
            let distribution = this.distribution();
            let filters = merchandising.facetFilters();
            let totalHits = this.totalHits();
            let searches = this.valueSearches();
            let expandedValues = this.expandedValues();
            let hiddenFacets = this.hiddenFacets();
            let collapsedFacets = this.collapsedFacets();

            return (function() {
                let config = this.getEffectiveConfig(facet);
                let state = this.getValueState(facet.code);
                let sortRemainingBy = state.sortRemainingBy();
                let selected = filters[facet.code] || [];
                let counts = distribution[facet.code] || {};
                let search = (searches[facet.code] || '').trim().toLowerCase();
                let pinned = state.order();
                let hidden = state.hidden();
                let options = Object.keys(counts).map(function(value) {
                    return this.buildOption(value, counts[value], selected);
                }, this);
                let pinnedOptions = pinned.map(function(value) {
                    return options.find(function(option) {
                        return option.value === value;
                    }) || this.buildOption(value, 0, selected);
                }, this);
                let others = facetService.orderOptions(options.filter(function(option) {
                    return pinned.indexOf(option.value) === -1;
                }), {sortValuesBy: sortRemainingBy !== 'default' && sortRemainingBy !== 'hidden' ? sortRemainingBy : config.sortValuesBy});
                let visibleOthers = sortRemainingBy === 'hidden' ? [] : others.filter(function(option) {
                    return hidden.indexOf(option.value) === -1;
                });
                let hiddenOthers = others.filter(function(option) {
                    return sortRemainingBy === 'hidden' || hidden.indexOf(option.value) !== -1;
                });
                let matches = function(option) {
                    return !search || option.label.toLowerCase().indexOf(search) !== -1;
                };
                let isExpanded = expandedValues.indexOf(facet.code) !== -1;
                let canCollapse = config.showMore && !search && !selected.length
                    && pinnedOptions.length + visibleOthers.length > config.showMoreLimit;
                let otherLimit = canCollapse && !isExpanded ? Math.max(0, config.showMoreLimit - pinnedOptions.length) : visibleOthers.length;
                let countValues = Object.values(counts);

                return {
                    facet: facet,
                    config: config,
                    code: facet.code,
                    label: facet.label,
                    isPrice: facet.renderRegion === 'price',
                    isHidden: hiddenFacets.indexOf(facet.code) !== -1,
                    isCollapsed: collapsedFacets.indexOf(facet.code) !== -1,
                    isEmpty: countValues.length === 0,
                    isNonDiscriminant: config.hideIfNonDiscriminant && countValues.length > 0 && countValues.every(function(count) {
                        return count === totalHits;
                    }),
                    pinnedOptions: pinnedOptions.filter(matches),
                    options: visibleOthers.slice(0, otherLimit).filter(matches),
                    hiddenOptions: hiddenOthers.filter(matches),
                    moreCount: visibleOthers.length - otherLimit,
                    canCollapse: canCollapse,
                    isExpanded: isExpanded,
                    hasOverrides: this.hasSettingOverrides(facet.code)
                };
            }).call(this);
        },

        buildOption: function(value, count, selected) {
            return {
                value: value,
                label: this.getValueLabel(value),
                count: count,
                swatch: this.getSwatchColor(value),
                selected: selected.indexOf(value) !== -1
            };
        },

        getActiveFilters: function() {
            let filters = merchandising.facetFilters();
            let facets = this.facets();
            let active = [];

            Object.keys(filters).forEach(function(code) {
                let facet = facets.find(function(item) {
                    return item.code === code;
                });

                (filters[code] || []).forEach(function(value) {
                    active.push({code: code, value: value, facetLabel: facet ? facet.label : code, label: this.getValueLabel(value)});
                }, this);
            }, this);

            return active;
        },

        toggleFilter: function(code, value) {
            let filters = $.extend({}, merchandising.facetFilters());
            let values = (filters[code] || []).slice();
            let index = values.indexOf(value);

            if (index === -1) {
                values.push(value);
            } else {
                values.splice(index, 1);
            }

            if (values.length) {
                filters[code] = values;
            } else {
                delete filters[code];
            }

            merchandising.facetFilters(filters);
        },

        removeFilter: function(filter) {
            this.toggleFilter(filter.code, filter.value);
        },

        clearFilters: function() {
            merchandising.facetFilters({});
        },

        hasSettingOverrides: function(code) {
            let state = this.getSettingState(code);

            return state.showMoreLimit() !== '' || BOOLEAN_SETTINGS.some(function(setting) {
                return state[setting]() !== '';
            }) || this.getValueState(code).sortRemainingBy() !== 'default';
        },

        toggleCollapsed: function(code) {
            if (this.collapsedFacets().indexOf(code) === -1) {
                this.collapsedFacets.push(code);
            } else {
                this.collapsedFacets.remove(code);
            }
        },

        toggleExpandedValues: function(code) {
            if (this.expandedValues().indexOf(code) === -1) {
                this.expandedValues.push(code);
            } else {
                this.expandedValues.remove(code);
            }
        },

        toggleSettings: function(code) {
            this.settingsFacet(this.settingsFacet() === code ? null : code);
        },

        isSettingsOpen: function(code) {
            return this.settingsFacet() === code;
        },

        getValueSearch: function(code) {
            let self = this;

            if (!this.searchAccessors) {
                this.searchAccessors = {};
            }

            if (!this.searchAccessors[code]) {
                this.searchAccessors[code] = ko.pureComputed({
                    read: function() {
                        return self.valueSearches()[code] || '';
                    },
                    write: function(value) {
                        let searches = $.extend({}, self.valueSearches());

                        searches[code] = value;
                        self.valueSearches(searches);
                    }
                });
            }

            return this.searchAccessors[code];
        },

        toggleFacetVisibility: function(code) {
            if (this.hiddenFacets().indexOf(code) !== -1) {
                this.hiddenFacets.remove(code);
            } else {
                this.hiddenFacets.push(code);
            }
        },

        togglePin: function(code, value) {
            let state = this.getValueState(code);

            if (state.order().indexOf(value) !== -1) {
                state.order.remove(value);
            } else {
                state.hidden.remove(value);
                state.order.push(value);
            }
        },

        toggleValueVisibility: function(code, value) {
            let state = this.getValueState(code);

            if (state.hidden().indexOf(value) !== -1) {
                state.hidden.remove(value);
            } else {
                state.order.remove(value);
                state.hidden.push(value);
            }
        },

        onFacetSort: function(oldIndex, newIndex) {
            let order = this.facetOrder().slice();

            order.splice(newIndex, 0, order.splice(oldIndex, 1)[0]);
            this.facetOrder(order);
        },

        onPinnedSort: function(code, oldIndex, newIndex) {
            let state = this.getValueState(code);
            let order = state.order().slice();

            order.splice(newIndex, 0, order.splice(oldIndex, 1)[0]);
            state.order(order);
        },

        getValueLabel: function(value) {
            return String(value).split('|')[0];
        },

        getSwatchColor: function(value) {
            let parts = String(value).split('|');
            let color = parts[2] || '';

            if (parts[1] != 1 || !color) {
                return null;
            }

            return color[0] === '#' ? color : '#' + color;
        },

        getGlobalLabel: function(facet, setting) {
            let value = facet[setting];

            if (setting === 'showMoreLimit') {
                return $t('Global: %1').replace('%1', value);
            }

            if (setting === 'sortValuesBy') {
                return $t('Global: %1').replace('%1', {count: $t('Product count'), alpha: $t('Alphabetical')}[value] || $t('Default order'));
            }

            return $t('Global: %1').replace('%1', value ? $t('Yes') : $t('No'));
        },

        resetSettings: function(code) {
            let state = this.getSettingState(code);

            state.showMoreLimit('');
            BOOLEAN_SETTINGS.forEach(function(setting) {
                state[setting]('');
            });
            this.getValueState(code).sortRemainingBy('default');
        },

        getPriceRange: function(code) {
            let stats = this.stats()[code];
            let queryBuilder = registry.get(QUERY_BUILDER);
            let format = queryBuilder ? queryBuilder.priceFormat : null;

            if (!stats) {
                return '';
            }

            return priceUtils.formatPriceLocale(stats.min, format, false) + ' – ' + priceUtils.formatPriceLocale(stats.max, format, false);
        },

        save: function() {
            let self = this;

            this.request(this.ajaxUrl.saveFacets, {ordering: this.serialize()}).done(function(r) {
                if (!r.success) {
                    self.showMessage('error', r.message);
                    return;
                }

                self.hasOwnConfig(true);
                self.snapshot(self.serialize());
                self.showMessage('success', r.message);
            });
        },

        reset: function() {
            let self = this;

            confirm({
                title: $t('Use Global Configuration'),
                content: $t('The facet configuration of this category will be removed and the global configuration will apply. Do you want to continue?'),
                actions: {
                    confirm: function() {
                        self.request(self.ajaxUrl.resetFacets, {}).done(function(r) {
                            if (!r.success) {
                                self.showMessage('error', r.message);
                                return;
                            }

                            self.settingsFacet(null);
                            self.applyState(r);
                            self.showMessage('success', r.message);
                        });
                    }
                }
            });
        },

        openCopy: function() {
            let self = this;
            let element = $('#merch-facets-copy');

            if (!this.copyModal) {
                this.copyModal = modal({
                    type: 'popup',
                    title: $t('Copy Facet Configuration'),
                    modalClass: 'merch-facets-copy-modal',
                    buttons: [{
                        text: $t('Cancel'),
                        class: 'action-secondary action-dismiss',
                        click: function() {
                            this.closeModal();
                        }
                    }, {
                        text: $t('Copy Configuration'),
                        class: 'action-primary',
                        click: function() {
                            self.copy();
                        }
                    }]
                }, element);
                this.initCopyTree();
            }

            element.css('display', '');
            this.copyStoreIds([String(this.storeId)]);
            $('#merch-facets-copy-tree').jstree(true).deselect_all();
            element.modal('openModal');
        },

        initCopyTree: function() {
            let tree = registry.get(CATEGORY_TREE);
            let data = tree ? JSON.parse(tree.categoryTree) : [];
            let search = $('#merch-facets-copy-search');
            let element = $('#merch-facets-copy-tree');

            element.jstree({
                plugins: ['checkbox', 'search'],
                core: {
                    themes: {dots: false},
                    data: data
                },
                checkbox: {
                    three_state: false,
                    cascade: ''
                },
                search: {
                    show_only_matches: true,
                    show_only_matches_children: true
                }
            }).on('ready.jstree', function(event, treeData) {
                treeData.instance.open_node(treeData.instance.get_node('#').children);
            });

            search.on('input', function() {
                element.jstree(true).search(search.val().trim());
            });
        },

        copy: function() {
            let self = this;
            let categoryIds = $('#merch-facets-copy-tree').jstree(true).get_checked();

            if (!categoryIds.length || !this.copyStoreIds().length) {
                this.showMessage('error', $t('Select at least one category and one store view.'));
                return;
            }

            this.request(this.ajaxUrl.copyFacets, {
                targetCategoryIds: categoryIds,
                targetStoreIds: this.copyStoreIds(),
                includeChildren: this.copyIncludeChildren() ? 1 : 0,
                overwrite: this.copyOverwrite() ? 1 : 0
            }).done(function(r) {
                self.showMessage(r.success ? 'success' : 'error', r.message);

                if (r.success) {
                    $('#merch-facets-copy').modal('closeModal');
                }
            });
        },

        discard: function() {
            this.settingsFacet(null);
            this.loadState();
        },

        showMessage: function(type, text) {
            let queryBuilder = registry.get(QUERY_BUILDER);

            if (queryBuilder) {
                queryBuilder.showMessage(type, text);
            }
        },

        request: function(url, data, showLoader) {
            let self = this;

            return $.ajax(url, {
                type: 'POST',
                data: $.extend({
                    form_key: window.FORM_KEY,
                    storeId: this.storeId,
                    categoryId: merchandising.currentCategoryId()
                }, data),
                dataType: 'json',
                showLoader: showLoader !== false
            }).fail(function(xhr, status) {
                if (status !== 'abort') {
                    self.showMessage('error', $t('Something went wrong while processing the request.'));
                }
            });
        }
    });
});
