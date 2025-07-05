define([
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(ko, configModel, viewModeState) {
    'use strict';

    const defaultGridLimit = configModel.get('gridPerPage');
    const defaultListLimit = configModel.get('listPerPage');

    const state = {
        currentLimit: ko.observable(defaultGridLimit),
        availableLimits: ko.observableArray(configModel.get('gridPerPageValues')),

        resetLimitForMode: function(mode) {
            if (mode === 'grid') {
                state.availableLimits(configModel.get('gridPerPageValues'));
                state.currentLimit(defaultGridLimit);
            } else {
                state.availableLimits(configModel.get('listPerPageValues'));
                state.currentLimit(defaultListLimit);
            }
        }
    };

    viewModeState.currentViewMode.subscribe((mode) => {
        state.resetLimitForMode(mode);
    });

    return state;
});
