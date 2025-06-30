define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
], function(Component, ko, facetsState, searchState) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();

            this.currentPage = facetsState.currentPage;
            this.totalHits = searchState.totalHits;
            this.hitsPerPage = searchState.hitsPerPage;

            this.totalPages = ko.computed(() => {
                return Math.ceil(this.totalHits() / this.hitsPerPage());
            });

            this.visiblePages = ko.computed(() => {
                const total = this.totalPages();

                if (!Number.isFinite(total)) {
                    return [];
                }

                return Array.from({ length: total }, (_, i) => i + 1);
            });

            return this;
        },

        goToPage: function(page) {
            if (page >= 1 && page <= this.totalPages()) {
                this.currentPage(page);
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        },

        prevPage: function() {
            this.goToPage(this.currentPage() - 1);
        },

        nextPage: function() {
            this.goToPage(this.currentPage() + 1);
        },

        isCurrentPage: function(page) {
            return page === this.currentPage();
        },

        showPrevButton: function() {
            return this.currentPage() > 1;
        },

        showNextButton: function() {
            return this.currentPage() < this.totalPages();
        }
    });
});
