define([
    'uiComponent',
    'ko',
    'jquery',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Walkwizus_MeilisearchFrontend/js/service/facets',
    'Magento_Catalog/js/price-utils',
    'Magento_Swatches/js/swatch-renderer'
], function(Component, ko, $, facetsState, searchState, FacetsService, priceUtils) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Component.extend({
        defaults: {
            isFilterActive: ko.observable(false),
            isCurrentFilterExpanded: ko.observable(false)
        },

        initialize: function() {
            this._super();

            this.computedCurrentFacets = ko.pureComputed(() => {
                const selected = facetsState.selectedFacets();
                const config = meilisearchConfig.facets.facetConfig;
                const result = [];

                Object.entries(selected).forEach(([code, values]) => {
                    const facetCfg = config[code];
                    if (!facetCfg) return;

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
                const currentFilters = facetsState.selectedFacets();

                const availableFacets = FacetsService.getAvailableFacets(results);

                return availableFacets.map(facet => {
                    const options = ko.observableArray(facet.options);
                    const showAllOptions = ko.observable(false);
                    const hasSelection = ko.pureComputed(() =>
                        Array.isArray(currentFilters[facet.code]) && currentFilters[facet.code].length > 0
                    );

                    const visibleOptions = ko.pureComputed(() => {
                        if (!facet.showMore || hasSelection() || showAllOptions()) {
                            return options();
                        }
                        return options().slice(0, facet.showMoreLimit);
                    });

                    return {
                        ...facet,
                        options: options,
                        visibleOptions: visibleOptions,
                        showAllOptions: showAllOptions,
                        hasSelection: hasSelection,
                        min: ko.observable(results.facetStats?.[facet.code]?.min ?? null),
                        max: ko.observable(results.facetStats?.[facet.code]?.max ?? null)
                    };
                });
            });

            const updateGlobalState = (facets) => {
                if (facets && facets.length > 0) {
                    facetsState.computedFacets(facets);
                }
            };
            updateGlobalState(this.computedFacets());
            this.computedFacets.subscribe(updateGlobalState);

            this.isFilterActive.subscribe(isActive => {
                $('body').toggleClass('filter-active', isActive);
            });

            return this;
        },

        formatFacetValue: function(value, facetCfg) {
            if (facetCfg.type === 'price') {
                const [from, to] = value.split('_').map(parseFloat);
                return priceUtils.formatPriceLocale(from, this.priceFormat, false) +
                    ' - ' +
                    priceUtils.formatPriceLocale(to, this.priceFormat, false);
            }

            if (typeof value === 'string' && value.includes('|')) {
                return value.split('|')[0];
            }

            return value;
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
                $option.on('click', () => $tooltip.hide());
            });
            $(document).on('click', () => $tooltip.hide());
        }
    });
});
