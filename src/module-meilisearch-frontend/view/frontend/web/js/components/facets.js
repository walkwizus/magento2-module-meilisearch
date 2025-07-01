define([
    'uiComponent',
    'ko',
    'jquery',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Magento_Catalog/js/price-utils',
    'Magento_Swatches/js/swatch-renderer'
], function(Component, ko, $, configModel, facetsState, searchState, priceUtils) {
    'use strict';

    return Component.extend({
        defaults: {
            isFilterActive: ko.observable(false),
            isCurrentFilterExpanded: ko.observable(false)
        },

        initialize: function() {
            this._super();
            this.computedCurrentFacets = ko.pureComputed(() => {
                const selected = facetsState.selectedFacets();
                const config = configModel.get('facets').facetConfig;
                const result = [];

                Object.entries(selected).forEach(([code, values]) => {
                    const facetCfg = config[code];

                    if (!facetCfg) {
                        return;
                    }

                    values.forEach(value => {
                        result.push({
                            code: code,
                            label: facetCfg.label,
                            value: value,
                            valueLabel: this.formatFacetValue(value, facetCfg)
                        });
                    });
                });

                return result;
            });

            this.computedFacets = ko.pureComputed(() => {
                const results = searchState.searchResults();
                const facetDistribution = results.facetDistribution;
                const facetStats = results.facetStats;
                const facetConfig = configModel.get('facets').facetConfig;
                const facetList = configModel.get('facets').facetList;
                const currentFilters = facetsState.selectedFacets();

                return facetList
                    .filter(code => {
                        const values = facetDistribution?.[code];
                        const cfg = facetConfig?.[code];

                        if (!values || !cfg) {
                            return false;
                        }

                        if (cfg.hasOptions) {
                            return Object.keys(values).some(val => cfg.options?.[val]);
                        }

                        return Object.keys(values).length > 0;
                    })
                    .map(code => {
                        const cfg = facetConfig[code];
                        const values = facetDistribution?.[code] || {};

                        const optionsData = Object.entries(values).reduce((acc, [val, count]) => {
                            if (cfg.hasOptions) {
                                const opt = cfg.options?.[val];
                                if (opt) {
                                    acc.push({ ...opt, value: val, count });
                                }
                            } else {
                                acc.push({ value: val, count });
                            }
                            return acc;
                        }, []);

                        const originalOptions = [...optionsData];
                        const sortedOptions = this.sortFacetOptions([...optionsData], cfg.sortValuesBy);
                        const options = ko.observableArray(sortedOptions);
                        const showAllOptions = ko.observable(false);
                        const hasSelection = ko.pureComputed(() => Array.isArray(currentFilters[code]) && currentFilters[code].length > 0);
                        const visibleOptions = ko.pureComputed(() => {
                            if (!cfg.showMore || hasSelection() || showAllOptions()) {
                                return options();
                            }
                            return options().slice(0, cfg.showMoreLimit);
                        });

                        return {
                            ...cfg,
                            code,
                            originalOptions,
                            sortedOptions,
                            options,
                            visibleOptions,
                            showAllOptions,
                            hasSelection,
                            min: ko.observable(facetStats[code]?.min ?? null),
                            max: ko.observable(facetStats[code]?.max ?? null)
                        };
                    });
            });

            this.computedFacets.subscribe(facets => {
                facetsState.computedFacets(facets);
            });

            this.isFilterActive.subscribe(function(isActive) {
                $('body').toggleClass('filter-active', isActive);
            });

            return this;
        },

        sortFacetOptions: function(options, sortType) {
            if (sortType === 'alpha') {
                return options.sort((a, b) => a.label.localeCompare(b.label));
            }

            if (sortType === 'count') {
                return options.sort((a, b) => b.count - a.count);
            }

            return options;
        },

        formatFacetValue: function(value, facetCfg) {
            if (facetCfg.type === 'price') {
                const [from, to] = value.split('_').map(parseFloat);
                return priceUtils.formatPriceLocale(from, this.priceFormat, false) +
                    ' - ' +
                    priceUtils.formatPriceLocale(to, this.priceFormat, false);
            }

            return facetCfg.options?.[value]?.label || value;
        },

        removeFacet: function(code, value) {
            const current = { ...facetsState.selectedFacets() };

            if (!Array.isArray(current[code])) return;

            const newValues = current[code].filter(v => v !== value);

            if (newValues.length === 0) {
                facetsState.resetFacet(code);
            } else {
                facetsState.updateFacet(code, newValues);
            }
        },

        clearAllFacets: function() {
            facetsState.selectedFacets({});
        },

        toggleFilter: function() {
            this.isFilterActive(!this.isFilterActive());
        },

        toggleCurrentFilter: function() {
            this.isCurrentFilterExpanded(!this.isCurrentFilterExpanded());
        },

        renderSwatchTooltip: function(element) {
            const $tooltip = $('.swatch-option-tooltip');

            $(element).find('.swatch-option').each(function () {
                const $option = $(this);

                $option.SwatchRendererTooltip();

                $option.on('click', function () {
                    $tooltip.hide();
                });
            });

            $(document).on('click', function () {
                $tooltip.hide();
            });
        }
    });
});
