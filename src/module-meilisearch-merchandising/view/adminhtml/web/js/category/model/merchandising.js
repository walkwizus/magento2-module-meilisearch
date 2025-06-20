define([
    'ko',
    'jquery'
], function(ko) {
    'use strict';

    return {
        currentCategoryId: ko.observable(null),
        currentRule: ko.observable(null),
        docs: ko.observableArray(),
        docPositions: ko.observableArray(),
        message: ko.observable(null)
    };
});
