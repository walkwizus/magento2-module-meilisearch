define([
    'uiElement',
    'ko'
], function(Element, ko) {
    'use strict';

    return Element.extend({
        defaults: {
            facets: ko.observableArray([])
        },

        initialize: function() {
            this._super();
            return this;
        },

        search: function (facetCode, inputValue) {
            const facet = this.facets().find(f => f.code === facetCode);
            if (!facet || !facet.options || !facet.originalOptions) return;

            const searchTerm = inputValue.trim().toLowerCase();

            if (!searchTerm) {
                facet.options(facet.sortedOptions.slice());
                return;
            }

            const filtered = facet.originalOptions.filter(opt =>
                opt.label?.toLowerCase().includes(searchTerm)
            );

            facet.options(filtered);
        }
    });
});
