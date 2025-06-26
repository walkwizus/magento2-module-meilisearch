define([
    'uiComponent',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'Walkwizus_MeilisearchFrontend/js/service/query-builder',
    'Walkwizus_MeilisearchFrontend/js/model/facets-model'
], function (Component, ko, meilisearchService, queryBuilder, facetsModel) {
    'use strict';

    return Component.extend({
        defaults: {
            searchResults: ko.observable({}),
            totalHits: ko.observable(0),
            hitsPerPage: ko.observable(0),
            sortBy: ko.observable(null),
            isDescending: ko.observable(false)
        },

        initialize: function () {
            this._super();

            this.searchService = meilisearchService({
                host: this.host,
                apiKey: this.apiKey,
                indexName: this.indexName
            });

            facetsModel.searchQuery(new URLSearchParams(window.location.search).get('q') || '');
            facetsModel.currentPage(parseInt(new URLSearchParams(window.location.search).get('page')) || 1);

            this.performSearch();

            this.initSubscriptions();

            facetsModel.isInitializing(false);

            return this;
        },

        initSubscriptions: function () {
            let lastFilters = JSON.stringify(facetsModel.selectedFacets());

            facetsModel.selectedFacets.subscribe((f) => {
                const newFilters = JSON.stringify(f);
                if (!facetsModel.isInitializing() && newFilters !== lastFilters) {
                    lastFilters = newFilters;
                    facetsModel.currentPage(1);
                    this.performSearch();
                }
            });

            this.isDescending.subscribe(() => {
                if (!facetsModel.isInitializing()) {
                    this.performSearch();
                }
            });

            facetsModel.currentPage.subscribe(() => {
                if (!facetsModel.isInitializing()) {
                    this.performSearch();
                }
            });
        },

        updateSort: function (sortValue) {
            this.sortBy(sortValue);
            this.performSearch();
        },

        toggleSortDirection: function () {
            this.isDescending(!this.isDescending());
            this.performSearch();
        },

        performSearch: function () {
            const searchQuery = facetsModel.searchQuery();
            const sortBy = this.sortBy() ?? this.defaultSortBy;
            const sortDirection = this.isDescending() ? 'desc' : 'asc';
            const sortParams = sortBy ? [`${sortBy}:${sortDirection}`] : undefined;
            const selectedFilters = facetsModel.selectedFacets();
            const activeFacetCodes = Object.keys(selectedFilters);
            const currentPage = facetsModel.currentPage();

            if (activeFacetCodes.length === 0) {
                const filterParams = queryBuilder().buildFilters({}, this.currentCategoryId, this.categoryRule);

                this.searchService.search(searchQuery, {
                    filter: filterParams,
                    facets: this.facets.facetList,
                    sort: sortParams,
                    page: currentPage,
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
                page: currentPage,
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
                    page: currentPage,
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

        updateResults: function (results) {
            this.totalHits(results.totalHits);
            this.hitsPerPage(results.hitsPerPage);
            this.searchResults(results);
        }
    });
});
