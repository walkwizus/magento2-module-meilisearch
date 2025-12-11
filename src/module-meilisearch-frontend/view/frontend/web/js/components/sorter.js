define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/service/search',
    'Walkwizus_MeilisearchFrontend/js/model/sorter-state'
], function(Component, searchService, sorterState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Component.extend({
        initialize: function() {
            this._super();
            this.initSortOptions();

            this.isDescending = sorterState.isDescending;

            this.currentSort = meilisearchConfig.defaultSortBy;

            return this;
        },

        initSortOptions: function() {
            const availableSortBy = meilisearchConfig.availableSortBy || {};

            if (availableSortBy) {
                this.sortOptions = Object.entries(availableSortBy).map(([value, label]) => ({
                    value: value,
                    label: label
                }));
            }
        },

        updateSort: function(data, event) {
            const sortValue = event.target.value;
            this.currentSort = sortValue;
            sorterState.sortBy(sortValue);
        },

        toggleSortDirection: function() {
            this.isDescending(!this.isDescending());
            return false;
        }
    });
});
