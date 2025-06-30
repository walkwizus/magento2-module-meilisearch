define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/service/search'
], function(Component, searchService) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();
            searchService.init();
            return this;
        }
    });
});
