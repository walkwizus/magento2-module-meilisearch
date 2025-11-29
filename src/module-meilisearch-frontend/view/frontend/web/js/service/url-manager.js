(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.meilisearchUrlManager = factory();
    }
}(this, function() {
    'use strict';

    return function() {

    };
}));
