define([
    'ko'
], function(ko) {
    'use strict';

    return {
        currentCategoryId: ko.observable(null),
        currentCategoryName: ko.observable(''),
        currentCategoryPath: ko.observable(''),
        currentRule: ko.observable(null),
        ruleCategoryIds: ko.observableArray(),
        hasUnsavedChanges: ko.observable(false),
        docs: ko.observableArray()
    };
});
