define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state'
], function(Element, ko, configModel, facetsState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            return this;
        },

        search: function(facetCode, inputValue) {
            const facet = facetsState.computedFacets().find(f => f.code === facetCode);
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
