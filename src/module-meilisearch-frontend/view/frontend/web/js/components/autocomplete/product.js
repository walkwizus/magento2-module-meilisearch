define([
    'uiElement',
    'Walkwizus_MeilisearchFrontend/js/model/product-model',
], function(Element, productModel) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            this.productModel = productModel;
            return this;
        }
    });
});
