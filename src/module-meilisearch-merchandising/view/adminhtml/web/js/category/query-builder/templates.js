define([
    'mage/translate'
], function($t) {
    'use strict';

    function options(items, settings, translate, getValue, getLabel) {
        let optgroup = null;
        let html = '';

        items.forEach(function(item) {
            if (optgroup !== (item.optgroup || null)) {
                if (optgroup !== null) {
                    html += '</optgroup>';
                }

                optgroup = item.optgroup || null;

                if (optgroup !== null) {
                    html += '<optgroup label="' + translate(settings.optgroups[optgroup]) + '">';
                }
            }

            html += '<option value="' + getValue(item) + '">' + getLabel(item) + '</option>';
        });

        if (optgroup !== null) {
            html += '</optgroup>';
        }

        return html;
    }

    return {
        group: function(data) {
            let conditions = data.conditions.map(function(condition) {
                let id = data.group_id + '_cond_' + condition;

                return '<div class="admin__field-option">' +
                    '<input type="radio" class="admin__control-radio" id="' + id + '" name="' + data.group_id + '_cond" value="' + condition + '">' +
                    '<label class="admin__field-label" for="' + id + '">' + data.translate('conditions', condition) + '</label>' +
                '</div>';
            }).join('');

            let canAddGroup = data.settings.allow_groups === -1 || data.settings.allow_groups >= data.level;

            return '<div id="' + data.group_id + '" class="rules-group-container">' +
                '<div class="rules-group-header">' +
                    '<div class="group-conditions">' +
                        '<span class="merch-qb-label">' + $t('Match') + '</span>' +
                        conditions +
                        '<span class="merch-qb-label">' + $t('of the following conditions') + '</span>' +
                    '</div>' +
                    (data.settings.display_errors ? '<div class="error-container"></div>' : '') +
                    '<div class="group-actions">' +
                        '<button type="button" class="action-default" data-add="rule"><span>' + data.translate('add_rule') + '</span></button>' +
                        (canAddGroup ? '<button type="button" class="action-default" data-add="group"><span>' + data.translate('add_group') + '</span></button>' : '') +
                        (data.level > 1 ? '<button type="button" class="merch-qb-delete" data-delete="group" title="' + data.translate('delete_group') + '"><span>' + data.translate('delete_group') + '</span></button>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="rules-group-body"><div class="rules-list"></div></div>' +
            '</div>';
        },

        rule: function(data) {
            return '<div id="' + data.rule_id + '" class="rule-container">' +
                '<div class="rule-filter-container"></div>' +
                '<div class="rule-operator-container"></div>' +
                '<div class="rule-value-container"></div>' +
                '<div class="rule-header">' +
                    '<div class="rule-actions">' +
                        '<button type="button" class="merch-qb-delete" data-delete="rule" title="' + data.translate('delete_rule') + '"><span>' + data.translate('delete_rule') + '</span></button>' +
                    '</div>' +
                '</div>' +
                (data.settings.display_errors ? '<div class="error-container"></div>' : '') +
            '</div>';
        },

        filterSelect: function(data) {
            return '<select class="admin__control-select" name="' + data.rule.id + '_filter">' +
                (data.settings.display_empty_filter ? '<option value="-1">' + data.settings.select_placeholder + '</option>' : '') +
                options(data.filters, data.settings, data.translate, function(filter) {
                    return filter.id;
                }, function(filter) {
                    return data.translate(filter.label);
                }) +
            '</select>';
        },

        operatorSelect: function(data) {
            let single = data.operators.length === 1;

            return (single ? '<span class="merch-qb-operator">' + data.translate('operators', data.operators[0].type) + '</span>' : '') +
                '<select class="admin__control-select' + (single ? ' merch-qb-hidden' : '') + '" name="' + data.rule.id + '_operator">' +
                    options(data.operators, data.settings, data.translate, function(operator) {
                        return operator.type;
                    }, function(operator) {
                        return data.translate('operators', operator.type);
                    }) +
                '</select>';
        },

        ruleValueSelect: function(data) {
            let filter = data.rule.filter;

            return '<select class="' + (filter.multiple ? 'admin__control-multiselect' : 'admin__control-select') + '" name="' + data.name + '"' + (filter.multiple ? ' multiple size="5"' : '') + '>' +
                (filter.placeholder ? '<option value="' + filter.placeholder_value + '" disabled selected>' + filter.placeholder + '</option>' : '') +
                options(filter.values, data.settings, data.translate, function(value) {
                    return value.value;
                }, function(value) {
                    return value.label;
                }) +
            '</select>';
        }
    };
});
