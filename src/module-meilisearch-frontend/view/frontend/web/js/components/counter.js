define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-model'
], function(Element, ko, facetModels) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            this.observe([
                'totalHits',
                'hitsPerPage'
            ]);

            this.currentPage = facetModels.currentPage;

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
