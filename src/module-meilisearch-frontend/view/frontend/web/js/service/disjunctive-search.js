(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.meilisearchDisjunctiveSearch = factory();
    }
}(this, function() {
    'use strict';

    function buildDisjunctiveQueries(config) {
        const {
            indexName,
            query,
            selectedFacets = {},
            facetList = [],
            buildFilters,
            page = 1,
            hitsPerPage = 12,
            sort
        } = config;

        const activeCodes = Object.keys(selectedFacets);
        const queries = [];

        const mainFilters = buildFilters ? buildFilters(selectedFacets) : [];
        queries.push({
            indexUid: indexName,
            q: query,
            filter: mainFilters.length ? mainFilters : null,
            facets: facetList,
            page,
            hitsPerPage,
            sort
        });

        activeCodes.forEach((code) => {
            const excludeFilters = { ...selectedFacets };
            delete excludeFilters[code];

            const disjunctiveFilters = buildFilters ? buildFilters(excludeFilters) : [];

            queries.push({
                indexUid: indexName,
                q: query,
                filter: disjunctiveFilters.length ? disjunctiveFilters : null,
                facets: [code],
                page,
                hitsPerPage
            });
        });

        return queries;
    }

    function mergeDisjunctiveResults(multiResults, activeCodes) {
        const mainResults = multiResults[0];
        const finalDistribution = { ...(mainResults.facetDistribution || {}) };

        activeCodes.forEach((code, index) => {
            const disjunctiveResults = multiResults[index + 1];
            if (disjunctiveResults && disjunctiveResults.facetDistribution && disjunctiveResults.facetDistribution[code]) {
                finalDistribution[code] = disjunctiveResults.facetDistribution[code];
            }
        });

        return {
            mainResults: mainResults,
            facetDistribution: finalDistribution
        };
    }

    return {
        buildDisjunctiveQueries: buildDisjunctiveQueries,
        mergeDisjunctiveResults: mergeDisjunctiveResults
    }
}));
