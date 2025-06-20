define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            sortOptions: [],
            currentSort: ko.observable(''),
            isDescending: ko.observable(false),
            exports: {
                currentSort: '${ $.provider }:sortBy',
                isDescending: '${ $.provider }:isDescending'
            }
        },

        initialize: function () {
            this._super();
            this.initSortOptions();
            this.currentSort(this.defaultSortBy);
            return this;
        },

        initSortOptions: function () {
            if (this.availableSortBy) {
                this.sortOptions = Object.entries(this.availableSortBy).map(([value, label]) => ({
                    value: value,
                    label: label
                }));
            }
        },

        updateSort: function (data, event) {
            this.currentSort(event.target.value);
        },

        toggleSortDirection: function () {
            this.isDescending(!this.isDescending());
            return false;
        },
    });
});
