define([
    'uiElement',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
], function(Element, facetsState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            this.selectedFacets = facetsState.selectedFacets;
            return this;
        },

        toggle: function(code, value) {
            const current = { ...this.selectedFacets() };

            if (!current[code]) {
                current[code] = [];
            }

            const index = current[code].indexOf(value);

            if (index === -1) {
                current[code].push(value);
            } else {
                current[code].splice(index, 1);
                if (current[code].length === 0) {
                    delete current[code];
                }
            }

            this.selectedFacets(current);

            return true;
        }
    });
});
