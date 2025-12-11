define([
    'uiElement',
    'ko',
    'fuse',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state'
], function (Element, ko, Fuse, facetsState) {
    'use strict';

    const meilisearchConfig = window.meilisearchFrontendConfig;

    return Element.extend({
        initialize: function () {
            this._super();
            this.facetConfig = meilisearchConfig.facets.facetConfig;
            this.fuseInstances = {};
            this.observeFacetChanges();
            return this;
        },

        observeFacetChanges: function() {
            facetsState.computedFacets.subscribe(facets => {
                facets.forEach(facet => {
                    let facetConfig = this.facetConfig[facet.code];
                    if (facetConfig.searchboxFuzzyEnabled) {
                        this.fuseInstances[facet.code] = new Fuse(facet.options(), {
                            keys: ['label'],
                            threshold: 0.3,
                            minMatchCharLength: 1
                        });
                    }
                });
            });
        },

        search: function(facetCode, inputValue) {
            const facet = facetsState.computedFacets().find(f => f.code === facetCode);
            if (!facet || !facet.options) return;

            const facetConfig = this.facetConfig[facetCode];
            const searchTerm = inputValue.trim();

            if (!searchTerm) {
                facet.options(facet.sortedOptions.slice());
                return;
            }

            if (facetConfig.searchboxFuzzyEnabled) {
                const fuse = this.fuseInstances[facetCode];
                if (!fuse) return;

                const results = fuse.search(searchTerm).map(result => result.item);
                facet.options(results);
            } else {
                const filtered = facet.sortedOptions.filter(opt =>
                    opt.label?.toLowerCase().includes(searchTerm.toLowerCase())
                );

                facet.options(filtered);
            }
        }
    });
});
