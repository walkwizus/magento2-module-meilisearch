define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
], function(Element, ko, facetsState, searchState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();

            this.totalHits = searchState.totalHits;
            this.hitsPerPage = searchState.hitsPerPage;
            this.currentPage = facetsState.currentPage;

            this.from = ko.pureComputed(() => {
                if (this.totalHits() === 0) {
                    return 0;
                }
                return ((this.currentPage() - 1) * this.hitsPerPage()) + 1;
            });

            this.to = ko.pureComputed(() => {
                return Math.min(this.currentPage() * this.hitsPerPage(), this.totalHits());
            });

            return this;
        }
    });
});
