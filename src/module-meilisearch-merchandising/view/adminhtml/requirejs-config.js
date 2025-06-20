var config = {
    paths: {
        'jstree': 'Walkwizus_MeilisearchMerchandising/js/lib/jstree.min',
        'jquery-extendext': 'Walkwizus_MeilisearchMerchandising/js/lib/jquery-extendext',
        'queryBuilder': 'Walkwizus_MeilisearchMerchandising/js/lib/query-builder.min'
    },
    shim: {
        'jstree': {
            deps: ['jquery']
        },
        'queryBuilder': {
            deps: ['jquery', 'jquery-extendext']
        }
    }
};
