define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(Element, ko, viewModeState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Element.extend({
        initialize: function() {
            this._super();
            this.availableViewMode = meilisearchConfig.availableViewMode;
            viewModeState.currentViewMode(meilisearchConfig.defaultViewMode);

            return this;
        },

        getViewModeEntries: function () {
            return Object.entries(this.availableViewMode).map(([code, label]) => ({ code, label }));
        },

        switchViewMode: function(mode, event) {
            event.preventDefault();
            viewModeState.currentViewMode(mode);
        },

        isActive: function(mode) {
            return viewModeState.currentViewMode() === mode;
        }
    });
});
