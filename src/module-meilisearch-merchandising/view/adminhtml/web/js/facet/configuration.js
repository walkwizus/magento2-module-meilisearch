define([
    'uiComponent',
    'jquery',
    'ko',
    'mage/translate',
    'jquery/ui',
    'mage/validation'
], function (Component, $, ko, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Walkwizus_MeilisearchMerchandising/facet/configuration',
            successMessage: ko.observable(''),
            errorMessage: ko.observable('')
        },

        initialize: function () {
            this._super();
            this.facetsConfig = ko.observableArray(
                this.attributes.map(attr => ({
                    code: attr.code,
                    label: attr.label,
                    type: attr.type,
                    position: ko.observable(attr.position || 0),
                    operator: ko.observable(attr.operator || 'or'),
                    show_more: ko.observable(parseInt(attr.show_more) === 1),
                    show_more_limit: ko.observable(attr.show_more_limit || 10),
                    searchable: ko.observable(parseInt(attr.searchable) === 1),
                    searchbox_fuzzy_enabled: ko.observable(parseInt(attr.searchbox_fuzzy_enabled) === 1),
                    sort_values_by: ko.observable(attr.sort_values_by || 'magento'),
                    hide_if_non_discriminant: ko.observable(parseInt(attr.hide_if_non_discriminant) === 1),
                    expanded: ko.observable(false)
                }))
            );
            this.sortFacets();
            return this;
        },

        initSortable: function(element) {
            $(element).sortable({
                items: '.facet-card',
                handle: '.facet-drag-handle',
                tolerance: 'pointer',
                placeholder: 'ui-sortable-placeholder',
                forcePlaceholderSize: true,
                stop: () => {
                    this.updatePositions();
                }
            });
        },

        sortFacets: function() {
            this.facetsConfig.sort((a, b) => a.position() - b.position());
        },

        updatePositions: function() {
            $('.facet-card').each((index, element) => {
                const code = $(element).data('facet-code');
                const facet = this.getFacetConfig(code);
                if (facet) {
                    facet.position(index);
                }
            });
            this.sortFacets();
        },

        save: function () {
            const self = this;
            const config = this.facetsConfig().map(facet => ({
                code: facet.code,
                position: facet.position(),
                searchable: facet.searchable(),
                searchbox_fuzzy_enabled: facet.searchbox_fuzzy_enabled(),
                show_more: facet.show_more(),
                show_more_limit: facet.show_more_limit(),
                sort_values_by: facet.sort_values_by(),
                hide_if_non_discriminant: facet.hide_if_non_discriminant(),
            }));

            $.ajax({
                url: this.saveUrl,
                method: 'POST',
                data: {
                    form_key: window.FORM_KEY,
                    facets: JSON.stringify(config)
                },
                dataType: 'json',
                showLoader: true,
                success: function(response) {
                    if (response.success) {
                        self.showSuccessMessage($t('Configuration saved successfully'));
                    } else {
                        self.showErrorMessage(response.message || $t('Error saving configuration'));
                    }
                },
                error: function(error) {
                    self.showErrorMessage($t('Error saving configuration'));
                    console.error('Error saving configuration:', error);
                }
            });
        },

        showSuccessMessage: function(message) {
            this.successMessage(message);
        },

        showErrorMessage: function(message) {
            this.errorMessage(message);
        },

        getFacetConfig: function (code) {
            return this.facetsConfig().find(facet => facet.code === code) || null;
        },

        isBoolean: function (type) {
            return type === 'boolean';
        },

        isPrice: function (type) {
            return type === 'price';
        }
    });
});
