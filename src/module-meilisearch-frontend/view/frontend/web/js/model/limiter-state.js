define([
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(ko, viewModeState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;
    const defaultGridLimit = meilisearchConfig.gridPerPage;
    const defaultListLimit = meilisearchConfig.listPerPage;

    const state = {
        currentLimit: ko.observable(defaultGridLimit),
        availableLimits: ko.observableArray(meilisearchConfig.gridPerPageValues),

        resetLimitForMode: function(mode) {
            if (mode === 'grid') {
                state.availableLimits(meilisearchConfig.gridPerPageValues);
                state.currentLimit(defaultGridLimit);
            } else {
                state.availableLimits(meilisearchConfig.listPerPageValues);
                state.currentLimit(defaultListLimit);
            }
        }
    };

    viewModeState.currentViewMode.subscribe((mode) => {
        state.resetLimitForMode(mode);
    });

    return state;
});
