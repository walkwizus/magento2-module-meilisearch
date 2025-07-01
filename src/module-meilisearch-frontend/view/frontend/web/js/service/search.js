define([
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'Walkwizus_MeilisearchFrontend/js/service/query-builder',
    'Walkwizus_MeilisearchFrontend/js/model/config-model',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Walkwizus_MeilisearchFrontend/js/model/sorter-state'
], function (ko, meilisearchService, queryBuilder, configModel, facetsState, searchState, sorterState) {
    'use strict';

    let searchService;

    function init() {
        searchService = meilisearchService({
            host: configModel.get('host'),
            apiKey: configModel.get('apiKey'),
            indexName: configModel.get('indexName')
        });

        facetsState.searchQuery(new URLSearchParams(window.location.search).get('q') || '');
        facetsState.currentPage(parseInt(new URLSearchParams(window.location.search).get('page')) || 1);

        performSearch();

        initSubscription();

        searchState.isInitializing(false);
    }

    function initSubscription() {
        let lastFilters = JSON.stringify(facetsState.selectedFacets());

        facetsState.selectedFacets.subscribe((f) => {
            const newFilters = JSON.stringify(f);
            if (!searchState.isInitializing() && newFilters !== lastFilters) {
                lastFilters = newFilters;
                facetsState.currentPage(1);
                performSearch();
            }
        });

        facetsState.currentPage.subscribe(() => {
            if (!searchState.isInitializing()) {
                performSearch();
            }
        });

        sorterState.sortBy.subscribe(() => {
            if (!searchState.isInitializing()) {
                performSearch();
            }
        });

        sorterState.isDescending.subscribe(() => {
            if (!searchState.isInitializing()) {
                performSearch();
            }
        });
    }

    function performSearch() {
        const searchQuery = facetsState.searchQuery();
        const sortField = sorterState.sortBy() ?? configModel.get('defaultSortBy');
        const sortDirection = sorterState.isDescending() ? 'desc' : 'asc';
        const sortParams = sortField ? [`${sortField}:${sortDirection}`] : undefined;
        const selectedFilters = facetsState.selectedFacets();
        const activeFacetCodes = Object.keys(selectedFilters);
        const currentPage = facetsState.currentPage();
        const facetList = configModel.get('facets', {}).facetList || [];
        const gridPerPage = configModel.get('gridPerPage');

        if (activeFacetCodes.length === 0) {
            const filterParams = queryBuilder().buildFilters({}, configModel.get('currentCategoryId'), configModel.get('categoryRule'));

            searchService.search(searchQuery, {
                filter: filterParams,
                facets: facetList,
                sort: sortParams,
                page: currentPage,
                hitsPerPage: gridPerPage
            }).then(updateResults);

            return;
        }

        const queries = [];
        const mainFilterParams = queryBuilder().buildFilters(selectedFilters, configModel.get('currentCategoryId'), configModel.get('categoryRule'));

        queries.push({
            indexUid: configModel.get('indexName'),
            q: searchQuery,
            filter: mainFilterParams,
            facets: facetList,
            sort: sortParams,
            page: currentPage,
            hitsPerPage: gridPerPage
        });

        activeFacetCodes.forEach(facetCode => {
            const facetExcludedFilters = { ...selectedFilters };
            delete facetExcludedFilters[facetCode];

            const disjunctiveFilterParams = queryBuilder().buildFilters(facetExcludedFilters, configModel.get('currentCategoryId'), configModel.get('categoryRule'));

            queries.push({
                indexUid: configModel.get('indexName'),
                q: searchQuery,
                filter: disjunctiveFilterParams,
                facets: [facetCode],
                sort: sortParams,
                page: currentPage,
                hitsPerPage: gridPerPage
            });
        });

        searchService.multiSearch(queries)
            .then(response => {
                const mainResults = response.results[0];
                let facetDistributions = { ...mainResults.facetDistribution };

                activeFacetCodes.forEach((facetCode, index) => {
                    const disjunctiveResult = response.results[index + 1];
                    facetDistributions[facetCode] = disjunctiveResult.facetDistribution[facetCode];
                });

                const combinedResults = { ...mainResults };
                combinedResults.facetDistribution = facetDistributions;

                updateResults(combinedResults);
            });
    }

    function updateResults(results) {
        searchState.searchResults(results);
        searchState.totalHits(results.totalHits);
        searchState.hitsPerPage(results.hitsPerPage);
    }

    return {
        init: init
    };
});
