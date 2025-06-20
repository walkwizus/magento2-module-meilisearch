define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'Walkwizus_MeilisearchFrontend/js/service/query-builder',
    'Walkwizus_MeilisearchFrontend/js/model/facets-model'
], function(Component, ko, meilisearchService, queryBuilder, facetsModel) {
    'use strict';

    return Component.extend({
        defaults: {
            searchResults: ko.observable({}),
            totalHits: ko.observable(0),
            hitsPerPage: ko.observable(0),
            currentPage: ko.observable(1),
            sortBy: ko.observable(''),
            isDescending: ko.observable(false)
        },

        initialize: function() {
            this._super();

            this.initialized = false;

            this.searchService = meilisearchService({
                host: this.host,
                apiKey: this.apiKey,
                indexName: this.indexName
            });

            this.query = new URLSearchParams(window.location.search).get('q') || '';

            let lastFilters = JSON.stringify(facetsModel.selectedFacets());
            facetsModel.selectedFacets.subscribe((f) => {
                const newFilters = JSON.stringify(f);
                if (this.initialized && newFilters !== lastFilters) {
                    lastFilters = newFilters;
                    this.currentPage(1);
                    this.performSearch();
                }
            });

            this.sortBy.subscribe(() => {
                if (this.initialized) {
                    this.performSearch();
                }
            });

            this.isDescending.subscribe(() => {
                if (this.initialized) {
                    this.performSearch();
                }
            });

            this.currentPage.subscribe(() => {
                if (this.initialized) {
                    this.performSearch();
                }
            });

            setTimeout(() => {
                this.performSearch();
                this.initialized = true;
            }, 0);

            return this;
        },

        performSearch: function() {
            const searchQuery = this.query;
            const sortBy = this.sortBy();
            const sortDirection = this.isDescending() ? 'desc' : 'asc';
            const sortParams = sortBy ? [`${sortBy}:${sortDirection}`] : undefined;
            const selectedFilters = facetsModel.selectedFacets();
            const activeFacetCodes = Object.keys(selectedFilters);

            if (activeFacetCodes.length === 0) {
                const filterParams = queryBuilder().buildFilters({}, this.currentCategoryId, this.categoryRule);

                this.searchService.search(searchQuery, {
                    filter: filterParams,
                    facets: this.facets.facetList,
                    sort: sortParams,
                    page: this.currentPage(),
                    hitsPerPage: this.gridPerPage
                })
                .then(results => {
                    this.updateResults(results);
                });
                return;
            }

            const queries = [];

            const mainFilterParams = queryBuilder().buildFilters(selectedFilters, this.currentCategoryId, this.categoryRule);

            queries.push({
                indexUid: this.indexName,
                q: searchQuery,
                filter: mainFilterParams,
                facets: this.facets.facetList,
                sort: sortParams,
                page: this.currentPage(),
                hitsPerPage: this.gridPerPage
            });

            activeFacetCodes.forEach(facetCode => {
                const facetExcludedFilters = { ...selectedFilters };
                delete facetExcludedFilters[facetCode];

                const disjunctiveFilterParams = queryBuilder().buildFilters(facetExcludedFilters, this.currentCategoryId, this.categoryRule);

                queries.push({
                    indexUid: this.indexName,
                    q: searchQuery,
                    filter: disjunctiveFilterParams,
                    facets: [facetCode],
                    sort: sortParams,
                    page: this.currentPage(),
                    hitsPerPage: this.gridPerPage
                });
            });

            this.searchService.multiSearch(queries)
                .then(response => {
                    const mainResults = response.results[0];

                    let facetDistributions = { ...mainResults.facetDistribution };

                    activeFacetCodes.forEach((facetCode, index) => {
                        const disjunctiveResult = response.results[index + 1];
                        facetDistributions[facetCode] = disjunctiveResult.facetDistribution[facetCode];
                    });

                    const combinedResults = { ...mainResults };
                    combinedResults.facetDistribution = facetDistributions;

                    this.updateResults(combinedResults);
                });
        },

        updateResults: function(results) {
            this.totalHits(results.totalHits);
            this.hitsPerPage(results.hitsPerPage);
            this.searchResults(results);
        }
    });
});
