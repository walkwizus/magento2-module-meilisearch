define([
    'uiElement',
    'ko'
], function(Element, ko) {
    'use strict';

    return Element.extend({
        defaults: {
            availableViewMode: {},
            currentViewMode: ko.observable(null),
            exports: {
                currentViewMode: '${ $.parentName }:currentViewMode'
            }
        },

        initialize: function() {
            this._super();
            const firstMode = Object.keys(this.availableViewMode)[0];
            this.currentViewMode(firstMode);
            return this;
        },

        getViewModeEntries: function () {
            return Object.entries(this.availableViewMode).map(([code, label]) => ({ code, label }));
        },

        switchViewMode: function(mode, event) {
            event.preventDefault();
            this.currentViewMode(mode);
        },

        isActive: function(mode) {
            return this.currentViewMode() === mode;
        }
    });
});
