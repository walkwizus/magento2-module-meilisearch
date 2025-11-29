define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/service/search',
    'Walkwizus_MeilisearchFrontend/js/service/config-manager',
    'Walkwizus_MeilisearchFrontend/js/model/sorter-state'
], function(Component, searchService, configManager, sorterState) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();
            this.initSortOptions();

            this.isDescending = sorterState.isDescending;

            this.currentSort = configManager.get('defaultSortBy');

            return this;
        },

        initSortOptions: function() {
            const availableSortBy = configManager.get('availableSortBy', {});

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
