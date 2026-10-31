(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.MeilisearchFacets = factory();
    }
}(this, function() {
    'use strict';

    // Option labels are stored as HTML in Magento (Luma's sample data has "Cocona&reg; performance fabric").
    function decodeLabel(label) {
        const text = String(label);

        if (text.indexOf('&') === -1 || typeof DOMParser === 'undefined') {
            return text;
        }

        return new DOMParser().parseFromString(text, 'text/html').documentElement.textContent;
    }

    function compareLabels(a, b) {
        return String(a.label || a.value || '').localeCompare(String(b.label || b.value || ''), undefined, {numeric: true, sensitivity: 'base'});
    }

    function sortOptions(options, sortBy) {
        if (sortBy === 'alpha') {
            return options.sort(compareLabels);
        }

        if (sortBy === 'count') {
            return options.sort((a, b) => b.count - a.count);
        }

        if (sortBy === 'hidden') {
            return [];
        }

        return options;
    }

    function orderOptions(options, config) {
        const ordering = config.valueOrdering || null;

        if (!ordering) {
            return sortOptions(options.slice(), config.sortValuesBy);
        }

        const hidden = ordering.hidden || [];
        const pinned = ordering.order || [];
        const sortBy = ordering.sortRemainingBy && ordering.sortRemainingBy !== 'default' ? ordering.sortRemainingBy : config.sortValuesBy;
        const visible = options.filter(option => hidden.indexOf(option.value) === -1);
        const pinnedOptions = pinned
            .map(value => visible.find(option => option.value === value))
            .filter(Boolean);
        const remaining = visible.filter(option => pinned.indexOf(option.value) === -1);

        return pinnedOptions.concat(sortOptions(remaining, sortBy));
    }

    function getAvailableFacets(results, config) {
        const meilisearchConfig = config || window.meilisearchFrontendConfig;

        if (!results || !results.facetDistribution) {
            return [];
        }

        const facetConfig = meilisearchConfig.facets.facetConfig || {};
        const facetList = meilisearchConfig.facets.facetList || [];
        const facetDistribution = results.facetDistribution || {};
        const totalHits = results.totalHits || 0;

        return facetList
            .filter(function(code) {
                const values = facetDistribution[code];

                if (!values || Object.keys(values).length === 0) {
                    return false;
                }

                const isUseless = (facetConfig[code] || {}).hideIfNonDiscriminant && Object.values(values).every(count => count === totalHits);
                return !isUseless;
            })
            .map(function(code) {
                const config = facetConfig[code] || {};
                const valuesDistribution = facetDistribution[code] || {};

                const options = Object.entries(valuesDistribution).map(function([rawValue, count]) {
                    const parts = rawValue.split('|');
                    const label = decodeLabel(parts[0]);
                    const swatchType = parts[1] || null;
                    let swatchValue = parts[2] || null;

                    if (swatchType == 1 && swatchValue && swatchValue[0] !== '#') {
                        swatchValue = '#' + swatchValue;
                    }

                    return {
                        value: rawValue,
                        label: label,
                        count: count,
                        swatchType: swatchType,
                        swatchValue: swatchValue
                    };
                });

                return Object.assign({}, config, {
                    code: code,
                    options: orderOptions(options, config),
                    isSwatch: config.isSwatch || false
                });
            })
            .filter(facet => facet.options.length > 0)
            .sort((a, b) => (a.position || 0) - (b.position || 0));
    }

    return {
        getAvailableFacets,
        orderOptions,
        decodeLabel
    };
}));
