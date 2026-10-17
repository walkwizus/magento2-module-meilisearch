define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'Walkwizus_MeilisearchMerchandising/js/category/model/merchandising',
    'jstree'
], function(Component, ko, $, confirm, $t, merchandising) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Walkwizus_MeilisearchMerchandising/category/tree',
            merchandising: merchandising,
            searchQuery: '',
            tree: null
        },

        initialize: function() {
            this._super();
            this.initTree();

            merchandising.ruleCategoryIds(this.ruleCategoryIds || []);
            merchandising.ruleCategoryIds.subscribe(this.refreshRuleBadges.bind(this));
            this.searchQuery.extend({rateLimit: {timeout: 250, method: 'notifyWhenChangesStop'}});
            this.searchQuery.subscribe(this.search.bind(this));

            return this;
        },

        initObservable: function() {
            this._super().observe(['searchQuery']);

            return this;
        },

        initTree: function() {
            let self = this;

            ko.bindingHandlers.initTree = {
                init: function (element) {
                    self.tree = $(element).jstree({
                        plugins: ['search', 'conditionalselect'],
                        core: {
                            multiple: false,
                            themes: {dots: false},
                            data: self.prepareNodes(JSON.parse(self.categoryTree))
                        },
                        search: {
                            show_only_matches: true,
                            show_only_matches_children: true
                        },
                        conditionalselect: self.canSelect.bind(self)
                    }).on('select_node.jstree', function(event, data) {
                        merchandising.currentCategoryName(data.node.text);
                        merchandising.currentCategoryPath(data.instance.get_path(data.node, ' / ') || '');
                        merchandising.currentCategoryId(data.node.id);
                    }).on('ready.jstree', function(event, data) {
                        data.instance.open_node(data.instance.get_node('#').children);
                    });
                }
            };
        },

        prepareNodes: function(nodes) {
            let ruleCategoryIds = (this.ruleCategoryIds || []).map(String);

            return nodes.map(function(node) {
                node.li_attr = {class: ruleCategoryIds.indexOf(String(node.id)) !== -1 ? 'has-rule' : ''};
                node.a_attr = {title: node.text};

                if (node.children) {
                    node.children = this.prepareNodes(node.children);
                }

                return node;
            }, this);
        },

        canSelect: function(node) {
            let instance = this.tree.jstree(true);

            if (!merchandising.hasUnsavedChanges()) {
                return true;
            }

            confirm({
                title: $t('Unsaved changes'),
                content: $t('Product positions have not been saved. Do you want to discard them?'),
                actions: {
                    confirm: function() {
                        merchandising.hasUnsavedChanges(false);
                        instance.deselect_all(true);
                        instance.select_node(node);
                    }
                }
            });

            return false;
        },

        refreshRuleBadges: function(categoryIds) {
            let instance = this.tree ? this.tree.jstree(true) : null;
            let ids = categoryIds.map(String);

            if (!instance) {
                return;
            }

            $.each(instance.get_json('#', {flat: true}), function(index, item) {
                let node = instance.get_node(item.id);
                let hasRule = ids.indexOf(String(node.id)) !== -1;

                if ((node.li_attr.class === 'has-rule') !== hasRule) {
                    node.li_attr.class = hasRule ? 'has-rule' : '';
                    instance.redraw_node(node);
                }
            });
        },

        search: function(query) {
            if (this.tree) {
                this.tree.jstree(true).search(query.trim());
            }
        },

        expandAll: function() {
            this.tree.jstree(true).open_all();
        },

        collapseAll: function() {
            let instance = this.tree.jstree(true);

            instance.close_all();
            instance.open_node(instance.get_node('#').children);
        }
    });
});
