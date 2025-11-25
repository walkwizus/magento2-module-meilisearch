define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/service/search'
], function(Component, searchService) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();

            let initialState = this.initialState || null;
            searchService.init(initialState);
            return this;
        }
    });
});
