define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/config-manager',
    'Walkwizus_MeilisearchFrontend/js/model/viewmode-state'
], function(Element, ko, configManager, viewModeState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            this.availableViewMode = configManager.get('availableViewMode');
            viewModeState.currentViewMode(configManager.get('defaultViewMode'));

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
