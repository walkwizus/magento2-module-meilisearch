define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state'
], function(Component, ko, facetsState, searchState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Component.extend({
        defaults: {
            frameLength: 5
        },

        initialize: function() {
            this._super();

            this.currentCategoryUrl = meilisearchConfig.currentCategoryUrl;
            this.currentPage = facetsState.currentPage;
            this.totalHits = searchState.totalHits;
            this.hitsPerPage = searchState.hitsPerPage;

            this.totalPages = ko.computed(() => {
                return Math.ceil(this.totalHits() / this.hitsPerPage());
            });

            this.visiblePages = ko.computed(() => {
                const total = this.totalPages();

                if (!Number.isFinite(total) || total < 1) {
                    return [];
                }

                const half = Math.floor((this.frameLength - 1) / 2);
                let start = Math.max(1, this.currentPage() - half);
                const end = Math.min(total, start + this.frameLength - 1);

                if (end - start + 1 < this.frameLength && total >= this.frameLength) {
                    start = Math.max(1, total - this.frameLength + 1);
                }

                return Array.from({ length: end - start + 1 }, (_, i) => start + i);
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
