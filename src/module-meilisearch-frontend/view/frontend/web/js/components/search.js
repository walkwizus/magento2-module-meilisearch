define([
    'ko',
    'Walkwizus_MeilisearchFrontend/js/service/meilisearch-service',
    'Walkwizus_MeilisearchFrontend/js/service/query-builder',
    'Walkwizus_MeilisearchFrontend/js/service/disjunctive-search',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state',
    'Walkwizus_MeilisearchFrontend/js/model/search-state',
    'Walkwizus_MeilisearchFrontend/js/model/sorter-state',
    'Walkwizus_MeilisearchFrontend/js/model/limiter-state'
], function (
    ko,
    meilisearchService,
    queryBuilder,
    disjunctiveSearch,
    facetsState,
    searchState,
    sorterState,
    limiterState
) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;
    const hybridConfig = meilisearchConfig.hybridSearch || {};
    let searchService;

    function getHybridParams(searchQuery) {
        const params = {};

        if (hybridConfig.enabled && searchQuery && searchQuery.trim().length > 0) {
            params.hybrid = {
                semanticRatio: parseFloat(hybridConfig.semanticRatio),
                embedder: hybridConfig.embedder
            };

            if (hybridConfig.rankingScoreThreshold !== undefined && hybridConfig.rankingScoreThreshold !== null) {
                params.rankingScoreThreshold = parseFloat(hybridConfig.rankingScoreThreshold);
            }
        }

        return params;
    }

    function init(initialState) {
        searchService = meilisearchService({
            host: meilisearchConfig.host,
            apiKey: meilisearchConfig.apiKey,
            indexName: meilisearchConfig.indexName
        });

        updateResults(initialState);
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

        limiterState.currentLimit.subscribe(() => {
            if (!searchState.isInitializing()) {
                facetsState.currentPage(1);
                performSearch();
            }
        });
    }

    function performSearch() {
        searchState.isLoading(true);

        const searchQuery = facetsState.searchQuery();
        const sortField = sorterState.sortBy() ?? meilisearchConfig.defaultSortBy;
        const sortDirection = sorterState.isDescending() ? 'desc' : 'asc';
        const sortParams = sortField ? [`${sortField}:${sortDirection}`] : undefined;
        const selectedFilters = facetsState.selectedFacets();
        const activeFacetCodes = Object.keys(selectedFilters);
        const currentPage = facetsState.currentPage();
        const facetList = meilisearchConfig.facets.facetList;
        const hitsPerPage = limiterState.currentLimit();
        const hybridParams = getHybridParams(searchQuery);

        if (activeFacetCodes.length === 0) {
            const filterParams = queryBuilder.buildFilters({}, meilisearchConfig.currentCategoryId, meilisearchConfig.categoryRule);
            const searchParams = Object.assign({
                filter: filterParams,
                facets: facetList,
                sort: sortParams,
                page: currentPage,
                hitsPerPage: hitsPerPage
            }, hybridParams);

            searchService
                .search(searchQuery, searchParams)
                .then(updateResults)
                .finally(() => {
                    searchState.isLoading(false);
                });

            return;
        }

        const queries = disjunctiveSearch.buildDisjunctiveQueries({
            indexName: meilisearchConfig.indexName,
            query: searchQuery,
            selectedFacets: selectedFilters,
            facetList: facetList,
            buildFilters: function (sel) {
                return queryBuilder.buildFilters(
                    sel,
                    meilisearchConfig.currentCategoryId,
                    meilisearchConfig.categoryRule
                );
            },
            page: currentPage,
            hitsPerPage: hitsPerPage,
            sort: sortParams
        });

        if (hybridParams.hybrid) {
            queries.forEach(query => {
                query.hybrid = hybridParams.hybrid;
                if (hybridParams.rankingScoreThreshold !== undefined) {
                    query.rankingScoreThreshold = hybridParams.rankingScoreThreshold;
                }
            });
        }

        searchService.multiSearch(queries)
            .then(function (response) {
                const merged = disjunctiveSearch.mergeDisjunctiveResults(
                    response.results,
                    activeFacetCodes
                );

                const combinedResults = Object.assign({}, merged.mainResults, {
                    facetDistribution: merged.facetDistribution
                });

                updateResults(combinedResults);
            })
            .finally(function () {
                searchState.isLoading(false);
            });
    }

    function updateResults(results) {
        searchState.searchResults(results);
        searchState.totalHits(results.totalHits || 0);
        searchState.hitsPerPage(results.hitsPerPage);
    }

    return {
        init: init
    };
});
