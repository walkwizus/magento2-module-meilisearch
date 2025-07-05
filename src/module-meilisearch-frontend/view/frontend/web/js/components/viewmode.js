define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(Element, ko, configModel, viewModeState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            this.availableViewMode = configModel.get('availableViewMode');
            const defaultMode = Object.keys(this.availableViewMode)[0];
            viewModeState.currentViewMode(defaultMode);

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
