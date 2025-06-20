define([
    'uiComponent',
    'ko',
    'jquery',
    'jstree',
    'Walkwizus_MeilisearchMerchandising/js/category/model/merchandising'
], function(Component, ko, $, jstree, merchandising) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Walkwizus_MeilisearchMerchandising/category/tree',
            merchandising: merchandising
        },

        initialize: function() {
            this._super();
            this.initTree();

            return this;
        },

        initTree: function() {
            let self = this;

            ko.bindingHandlers.initTree = {
                init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
                    $(element).jstree({
                        'plugins': ['wholerow'],
                        'core': {
                            'multiple': false,
                            'data': JSON.parse(self.categoryTree)
                        }
                    }).on('select_node.jstree', function(node, selected) {
                        merchandising.currentCategoryId(selected.node.id);
                        merchandising.docs([]);
                    });
                }
            };
        }
    });
});
