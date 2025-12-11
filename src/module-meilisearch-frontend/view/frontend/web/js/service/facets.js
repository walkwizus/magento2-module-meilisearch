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

        return facetList
            .filter(function(code) {
                const values = facetDistribution[code];
                const config = facetConfig[code];

                if (!values || !config) {
                    return false;
                }

                if (config.hasOptions) {
                    return Object.keys(values).some(function(value) {
                        return config.options && config.options[value];
                    });
                }

                return Object.keys(values).length > 0;
            })
            .map(function(code) {
                const config = facetConfig[code] || {};
                const values = facetDistribution[code] || {};

                const options = Object.entries(values).map(function([val, count]) {
                    if (config.hasOptions && config.options && config.options[val]) {
                        const opt = config.options[val];
                        return Object.assign({}, opt, {
                            value: val,
                            count: count
                        });
                    }

                    return {
                        value: val,
                        count: count,
                        label: val
                    };
                });

                return Object.assign({}, config, {
                    code: code,
                    options: options
                });
            });
    }

    return {
        getAvailableFacets
    };
}));
