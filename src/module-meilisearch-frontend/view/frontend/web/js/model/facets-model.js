define([
    'ko'
], function(ko) {
    'use strict';

    const selectedFacets = ko.observable({});
    const currentPage = ko.observable(1);

    function updateFacet(code, values) {
        const current = { ...selectedFacets() };

        if (!values || (Array.isArray(values) && values.length === 0)) {
            delete current[code];
        } else {
            current[code] = values;
        }

        selectedFacets(current);
    }

    function resetFacet(code) {
        const current = { ...selectedFacets() };
        delete current[code];
        selectedFacets(current);
    }

    return {
        selectedFacets,
        currentPage,
        updateFacet,
        resetFacet
    };
});
