define([
    'ko'
], function(ko) {
    'use strict';

    return {
        isInitializing: ko.observable(true),
        isLoading: ko.observable(true),
        searchResults: ko.observable({}),
        totalHits: ko.observable(0),
        hitsPerPage: ko.observable(12),
        currentPage: ko.observable(1),
        searchQuery: ko.observable('')
    };
});
