define([
    'ko'
], function(ko) {
    'use strict';

    return {
        selectedFacets: ko.observable({}),
        computedFacets: ko.observable({}),
        searchQuery: ko.observable(''),
        currentPage: ko.observable(1),

        updateFacet: function(code, values) {
            const current = { ...this.selectedFacets() };
            if (!values || (Array.isArray(values) && values.length === 0)) {
                delete current[code];
            } else {
                current[code] = values;
            }
            this.selectedFacets(current);
        },

        resetFacet: function(code) {
            const current = { ...this.selectedFacets() };
            delete current[code];
            this.selectedFacets(current);
        }
    };
});
