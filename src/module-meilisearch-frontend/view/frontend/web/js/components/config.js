define([
    'uiComponent',
    'Walkwizus_MeilisearchFrontend/js/model/config-model'
], function(Component, configModel) {
    'use strict';

    return Component.extend({
        initialize: function() {
            this._super();
            configModel.set(this.meilisearchConfig);
            return this;
        }
    });
});
