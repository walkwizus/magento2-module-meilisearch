define([
    'ko'
], function(ko) {
    'use strict';

    return {
        sortBy: ko.observable(null),
        isDescending: ko.observable(false)
    };
});
