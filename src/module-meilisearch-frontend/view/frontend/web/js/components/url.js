define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/limiter-state'
], function(Element, ko, configModel, facetsState, viewModeState, limiterState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();

            this.facets = configModel.get('facets');
            this.currentPage = facetsState.currentPage;
            this.currentViewMode = viewModeState.currentViewMode;
            this.currentLimit = limiterState.currentLimit;

            this.restoreStateFromUrl();

            const getCurrentState = () => ({
                facets: facetsState.selectedFacets(),
                page: this.currentPage(),
                q: facetsState.searchQuery(),
                product_list_mode: this.currentViewMode(),
                product_list_limit: this.currentLimit()
            });

            facetsState.selectedFacets.subscribe(() => {
                this.updateUrl(getCurrentState());
            });

            this.currentPage.subscribe(() => {
                this.updateUrl(getCurrentState());
            });

            facetsState.searchQuery.subscribe(() => {
                this.updateUrl(getCurrentState());
            });

            this.currentViewMode.subscribe(() => {
                this.updateUrl(getCurrentState());
            });

            limiterState.currentLimit.subscribe(() => {
                this.updateUrl(getCurrentState());
            });

            window.addEventListener('popstate', () => {
                this.restoreStateFromUrl();
            });

            return this;
        },

        updateUrl: function(state) {
            const params = new URLSearchParams(window.location.search);

            Object.keys(this.facets.facetConfig).forEach(facetCode => {
                params.delete(facetCode);
            });

            Object.keys(state.facets || {}).forEach(facetCode => {
                const values = state.facets[facetCode];
                if (values.length > 0 && this.facets.facetConfig[facetCode]?.hasOptions) {
                    const labels = values.map(id => this.facets.facetConfig[facetCode].options[id]?.label).filter(Boolean);
                    if (labels.length > 0) {
                        params.set(facetCode, labels.join(','));
                    }
                } else if (values.length > 0) {
                    params.set(facetCode, values.join(','));
                }
            });

            Object.keys(state).forEach(key => {
                if (key === 'facets') {
                    return;
                }

                const value = state[key];

                if (key === 'product_list_mode') {
                    const defaultMode = configModel.get('listMode').split('-')[0];
                    if (value && value !== defaultMode) {
                        params.set(key, value);
                    } else {
                        params.delete(key);
                    }
                    return;
                }

                if (key === 'product_list_limit') {
                    const mode = state.product_list_mode || configModel.get('listMode').split('-')[0];
                    const defaultLimit = mode === 'grid' ? configModel.get('gridPerPage') : configModel.get('listPerPage');

                    if (parseInt(value) !== parseInt(defaultLimit)) {
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

            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            const currentUrl = window.location.pathname + window.location.search;

            if (newUrl !== currentUrl) {
                window.history.pushState(null, '', newUrl);
            }
        },

        restoreStateFromUrl: function() {
            const params = new URLSearchParams(window.location.search);
            const restoredFacets = {};
            let restoredPage = 1;

            params.forEach((value, key) => {
                if (key === 'page') {
                    const page = parseInt(value);
                    if (!isNaN(page) && page > 0) {
                        restoredPage = page;
                    }
                    return;
                }

                if (key === 'q') {
                    facetsState.searchQuery(value);
                    return;
                }

                if (key === 'product_list_mode') {
                    viewModeState.currentViewMode(value);
                    return;
                }

                if (key === 'product_list_limit') {
                    const mode = viewModeState.currentViewMode() || configModel.get('listMode').split('-')[0];
                    const defaultLimit = mode === 'grid' ? configModel.get('gridPerPage') : configModel.get('listPerPage');

                    limiterState.currentLimit(parseInt(value) || defaultLimit);
                    return;
                }

                if (!this.facets.facetConfig.hasOwnProperty(key)) {
                    return;
                }

                if (!value) {
                    return;
                }

                const labels = value.split(',').map(v => v.trim());
                const idList = [];

                if (this.facets.facetConfig[key]?.hasOptions) {
                    labels.forEach(label => {
                        const option = Object.values(this.facets.facetConfig[key].options)
                            .find(opt => opt.label.toLowerCase() === label.toLowerCase());
                        if (option) {
                            idList.push(option.value);
                        }
                    });
                } else {
                    labels.forEach(val => {
                        if (val) {
                            idList.push(val);
                        }
                    });
                }

                if (idList.length > 0) {
                    restoredFacets[key] = idList;
                }
            });

            facetsState.selectedFacets({ ...restoredFacets });
            facetsState.currentPage(restoredPage);
        }
    });
});
