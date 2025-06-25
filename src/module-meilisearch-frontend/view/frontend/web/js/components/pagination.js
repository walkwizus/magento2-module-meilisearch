define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-model'
], function(Component, ko, facetsModel) {
    'use strict';

    return Component.extend({
        defaults: {
            totalHits: ko.observable(0),
            hitsPerPage: ko.observable(12)
        },

        initialize: function() {
            this._super();

            this.currentPage = facetsModel.currentPage;

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
