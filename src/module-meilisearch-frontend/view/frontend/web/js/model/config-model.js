define([
    'underscore'
], function (_) {
    'use strict';

    const config = window.meilisearchFrontendConfig || {};

    return {
        get: function (key, defaultValue = null) {
            return _.has(config, key) ? config[key] : defaultValue;
        },

        set: function (key, value) {
            if (typeof key === 'object') {
                _.extend(config, key);
            } else {
                config[key] = value;
            }
        }
    };
});
