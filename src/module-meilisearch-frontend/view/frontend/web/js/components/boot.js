define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/components/search'
], function(Component, search) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();

            let initialState = this.initialState || null;
            search.init(initialState);
            return this;
        }
    });
});
