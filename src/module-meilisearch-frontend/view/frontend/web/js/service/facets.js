(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.MeilisearchFacets = factory();
    }
}(this, function() {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    function getAvailableFacets(results) {
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

                const isUseless = facetConfig[code].hideIfNonDiscriminant && Object.values(values).every(count => count === totalHits);
                return !isUseless;
            })
            .map(function(code) {
                const config = facetConfig[code] || {};
                const valuesDistribution = facetDistribution[code] || {};

                const options = Object.entries(valuesDistribution).map(function([rawValue, count]) {
                    const parts = rawValue.split('|');
                    const label = parts[0];
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
                    options: options,
                    isSwatch: config.isSwatch || false
                });
            })
            .sort((a, b) => (a.position || 0) - (b.position || 0));
    }

    return {
        getAvailableFacets
    };
}));
