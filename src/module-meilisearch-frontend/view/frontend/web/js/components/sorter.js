define([
    'ko',
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/model/sorter-state'
], function(ko, Component, sorterState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;
    const SORT_RELEVANCE = 'relevance';

    return Component.extend({
        initialize: function() {
            this._super();
            this.initSortOptions();

            this.isDescending = sorterState.isDescending;

            this.currentSort = meilisearchConfig.defaultSortBy;

            this.isDirectionDisabled = ko.pureComputed(
                () => (sorterState.sortBy() || meilisearchConfig.defaultSortBy) === SORT_RELEVANCE
            );

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
            if (!this.isDirectionDisabled()) {
                this.isDescending(!this.isDescending());
            }

            return false;
        }
    });
});
