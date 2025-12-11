(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.MeilisearchUrlManager = factory();
    }
}(this, function() {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    function getFacetsConfig() {
        const facets = meilisearchConfig.facets;
        return facets && facets.facetConfig ? facets.facetConfig : {};
    }

    function getDefaultLimitForMode(mode) {
        const m = (mode || 'grid').toLowerCase();
        if (m === 'list') {
            return meilisearchConfig.listPerPage;
        }
        return meilisearchConfig.gridPerPage;
    }

    function parseStateFromUrlInternal() {
        const params = new URLSearchParams(window.location.search);
        const facetConfig = getFacetsConfig();

        const restoredFacets = {};
        let restoredPage = 1;
        let restoredQuery = '';
        let restoredMode = null;
        let restoredLimit = null;

        params.forEach((value, key) => {
            if (key === 'page') {
                const page = parseInt(value, 10);
                if (!isNaN(page) && page > 0) {
                    restoredPage = page;
                }
                return;
            }

            if (key === 'q') {
                restoredQuery = value;
                return;
            }

            if (key === 'product_list_mode') {
                restoredMode = value;
                return;
            }

            if (key === 'product_list_limit') {
                restoredLimit = parseInt(value, 10) || null;
                return;
            }

            if (!Object.prototype.hasOwnProperty.call(facetConfig, key)) {
                return;
            }

            if (!value) return;

            const labels = value.split(',').map(v => v.trim());
            const idList = [];
            const def = facetConfig[key];

            if (def && def.hasOptions && def.options) {
                labels.forEach(label => {
                    const option = Object.values(def.options)
                        .find(opt => String(opt.label).toLowerCase() === label.toLowerCase());
                    if (option) {
                        idList.push(option.value);
                    }
                });
            } else {
                labels.forEach(val => {
                    if (val) idList.push(val);
                });
            }

            if (idList.length > 0) {
                restoredFacets[key] = idList;
            }
        });

        const mode = restoredMode || meilisearchConfig.defaultViewMode;
        const limit = restoredLimit != null
            ? restoredLimit
            : getDefaultLimitForMode(mode);

        return {
            facets: restoredFacets,
            page: restoredPage,
            q: restoredQuery,
            product_list_mode: mode,
            product_list_limit: limit
        };
    }

    function buildUrlFromState(state) {
        const params = new URLSearchParams(window.location.search);
        const facetConfig = getFacetsConfig();

        Object.keys(facetConfig).forEach(facetCode => {
            params.delete(facetCode);
        });

        Object.keys(state.facets || {}).forEach(facetCode => {
            const values = state.facets[facetCode] || [];
            if (!values.length) return;

            const def = facetConfig[facetCode];

            if (def && def.hasOptions && def.options) {
                const labels = values
                    .map(id => def.options[id]?.label)
                    .filter(Boolean);

                if (labels.length > 0) {
                    params.set(facetCode, labels.join(','));
                }
            } else {
                params.set(facetCode, values.join(','));
            }
        });

        Object.keys(state).forEach(key => {
            if (key === 'facets') return;

            const value = state[key];

            if (key === 'product_list_mode') {
                const defaultMode = meilisearchConfig.defaultViewMode;
                if (value && value !== defaultMode) {
                    params.set(key, value);
                } else {
                    params.delete(key);
                }
                return;
            }

            if (key === 'product_list_limit') {
                const mode = state.product_list_mode || meilisearchConfig.defaultViewMode;
                const defaultLimit = getDefaultLimitForMode(mode);

                if (parseInt(value, 10) !== parseInt(defaultLimit, 10)) {
                    params.set(key, value);
                } else {
                    params.delete(key);
                }
                return;
            }

            if (value && value !== '' && !(typeof value === 'number' && value <= 1)) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        });

        const query = params.toString();
        return window.location.pathname + (query ? '?' + query : '');
    }

    function updateUrl(state) {
        const newUrl = buildUrlFromState(state);
        const currentUrl = window.location.pathname + window.location.search;

        if (newUrl !== currentUrl) {
            window.history.pushState(null, '', newUrl);
        }
    }

    function getStateFromUrl() {
        return parseStateFromUrlInternal();
    }

    return {
        updateUrl,
        getStateFromUrl
    };
}));
