(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        root.MeilisearchQueryBuilder = factory();
    }
}(this, function() {
    'use strict';

    return {
        buildFilters: function(filters, categoryId, categoryRule) {
            const filterExpressions = [];

            if (categoryId) {
                filterExpressions.push(`category_ids = ${categoryId}`);
            }

            if (categoryRule) {
                filterExpressions.push(categoryRule);
            }

            if (filters && typeof filters === 'object') {
                Object.keys(filters).forEach(facetCode => {
                    const values = filters[facetCode];

                    if (Array.isArray(values) && values.length > 0) {
                        const expressions = values.map(value => {
                            if (typeof value === 'string' && /^\d+(\.\d+)?_\d+(\.\d+)?$/.test(value)) {
                                const [from, to] = value.split('_');
                                return `(${facetCode} >= ${from} AND ${facetCode} <= ${to})`;
                            }

                            const safeValue = String(value).replace(/"/g, '\\"');
                            return `${facetCode} = "${safeValue}"`;
                        });

                        if (expressions.length === 1) {
                            filterExpressions.push(expressions[0]);
                        } else {
                            filterExpressions.push(`(${expressions.join(' OR ')})`);
                        }
                    }
                });
            }

            return filterExpressions;
        }
    };
}));
