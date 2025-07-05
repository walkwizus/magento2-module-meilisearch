define([
    'uiElement',
    'Walkwizus_MeilisearchFrontend/js/model/limiter-state'
], function (Element, limiterState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();

            this.perPageValues = limiterState.availableLimits;
            this.currentLimit = limiterState.currentLimit;

            return this;
        }
    });
});
