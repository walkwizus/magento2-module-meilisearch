(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.meilisearchConfigManager = factory();
    }
}(this, function() {
    'use strict';

    const config = window.meilisearchFrontendConfig || {};

    function hasKey(object, key) {
        return Object.prototype.hasOwnProperty.call(object, key);
    }

    return {
        get: function(key, defaultValue) {
            return hasKey(config, key) ? config[key] : defaultValue;
        },

        set: function(key, value) {
            config[key] = value;
        }
    };
}));
