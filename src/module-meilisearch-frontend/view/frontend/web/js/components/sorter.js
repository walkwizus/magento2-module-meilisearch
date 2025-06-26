define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            sortOptions: [],
            currentSort: ko.observable(''),
            exports: {
                currentSort: '${ $.provider }:sortBy',
                isDescending: '${ $.provider }:isDescending'
            }
        },

        initialize: function () {
            this._super();
            this.initSortOptions();

            this.isDescending = ko.pureComputed({
                read: () => this.source.isDescending(),
                write: (value) => this.source.isDescending(value)
            });

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
            if (this.source && typeof this.source.updateSort === 'function') {
                this.source.updateSort(event.target.value);
            }
        },

        toggleSortDirection: function () {
            this.isDescending(!this.isDescending());
            return false;
        }
    });
});
