define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state',
    'Walkwizus_MeilisearchFrontend/js/model/limiter-state',
    'Walkwizus_MeilisearchFrontend/js/service/url-manager',
    'Walkwizus_MeilisearchFrontend/js/model/search-state'
], function(Element, ko, facetsState, viewModeState, limiterState, urlManager, searchState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Element.extend({
        initialize: function() {
            this._super();

            this.facets = meilisearchConfig.facets;
            this.currentPage = facetsState.currentPage;
            this.currentViewMode = viewModeState.currentViewMode;
            this.currentLimit = limiterState.currentLimit;

            const restored = urlManager.getStateFromUrl();

            facetsState.selectedFacets(restored.facets);
            // Once search has booted, its page comes from the server's result
            if (searchState.isInitializing()) {
                facetsState.currentPage(restored.page);
            }
            facetsState.searchQuery(restored.q);
            viewModeState.currentViewMode(restored.product_list_mode);
            limiterState.currentLimit(restored.product_list_limit);

            const getCurrentState = () => ({
                facets: facetsState.selectedFacets(),
                page: this.currentPage(),
                q: facetsState.searchQuery(),
                product_list_mode: this.currentViewMode(),
                product_list_limit: this.currentLimit()
            });

            const syncUrl = () => {
                const state = getCurrentState();
                urlManager.updateUrl(state, searchState.isInitializing());
            };

            facetsState.selectedFacets.subscribe(syncUrl);
            this.currentPage.subscribe(syncUrl);
            facetsState.searchQuery.subscribe(syncUrl);
            this.currentViewMode.subscribe(syncUrl);
            this.currentLimit.subscribe(syncUrl);

            if (!searchState.isInitializing()) {
                urlManager.updateUrl(getCurrentState(), true);
            }

            window.addEventListener('popstate', () => {
                const s = urlManager.getStateFromUrl();

                facetsState.selectedFacets(s.facets);
                facetsState.currentPage(s.page);
                facetsState.searchQuery(s.q);
                viewModeState.currentViewMode(s.product_list_mode);
                limiterState.currentLimit(s.product_list_limit);
            });

            return this;
        }
    });
});
