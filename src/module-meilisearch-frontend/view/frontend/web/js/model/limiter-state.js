define([
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/config-manager',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(ko, configManager, viewModeState) {
    'use strict';

    const defaultGridLimit = configManager.get('gridPerPage');
    const defaultListLimit = configManager.get('listPerPage');

    const state = {
        currentLimit: ko.observable(defaultGridLimit),
        availableLimits: ko.observableArray(configManager.get('gridPerPageValues')),

        resetLimitForMode: function(mode) {
            if (mode === 'grid') {
                state.availableLimits(configManager.get('gridPerPageValues'));
                state.currentLimit(defaultGridLimit);
            } else {
                state.availableLimits(configManager.get('listPerPageValues'));
                state.currentLimit(defaultListLimit);
            }
        }
    };

    viewModeState.currentViewMode.subscribe((mode) => {
        state.resetLimitForMode(mode);
    });

    return state;
});
