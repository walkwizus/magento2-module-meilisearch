define([
    'uiElement',
    'ko',
    'Walkwizus_MeilisearchFrontend/js/model/facets-model'
], function (Element, ko, facetsModel) {
    'use strict';

    return Element.extend({
        initialize: function () {
            this._super();

            this.currentPage = facetsModel.currentPage;

            this.restoreStateFromUrl();

            facetsModel.selectedFacets.subscribe(facets => {
                this.updateUrl(facets, this.currentPage(), facetsModel.searchQuery());
            });

            this.currentPage.subscribe(page => {
                this.updateUrl(facetsModel.selectedFacets(), page, facetsModel.searchQuery());
            });

            facetsModel.searchQuery.subscribe(q => {
                this.updateUrl(facetsModel.selectedFacets(), this.currentPage(), q);
            });

            window.addEventListener('popstate', () => {
                this.restoreStateFromUrl();
            });

            return this;
        },

        updateUrl: function (facets, page, q) {
            const params = new URLSearchParams(window.location.search);

            Object.keys(this.facets.facetConfig).forEach(facetCode => {
                params.delete(facetCode);
            });

            Object.keys(facets).forEach(facetCode => {
                const values = facets[facetCode];
                if (values.length > 0 && this.facets.facetConfig[facetCode]?.hasOptions) {
                    const labels = values.map(id => this.facets.facetConfig[facetCode].options[id]?.label).filter(Boolean);
                    if (labels.length > 0) {
                        params.set(facetCode, labels.join(','));
                    }
                } else if (values.length > 0) {
                    params.set(facetCode, values.join(','));
                }
            });

            if (page > 1) {
                params.set('page', page);
            } else {
                params.delete('page');
            }

            if (q && q.trim() !== '') {
                params.set('q', q);
            } else {
                params.delete('q');
            }

            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            const currentUrl = window.location.pathname + window.location.search;

            if (newUrl !== currentUrl) {
                window.history.pushState(null, '', newUrl);
            }
        },

        restoreStateFromUrl: function () {
            const params = new URLSearchParams(window.location.search);
            const restoredFacets = {};
            let restoredPage = 1;

            params.forEach((value, key) => {
                if (key === 'page') {
                    const page = parseInt(value);
                    if (!isNaN(page) && page > 0) {
                        restoredPage = page;
                    }
                    return;
                }

                if (key === 'q') {
                    facetsModel.searchQuery(value);
                    return;
                }

                if (!this.facets.facetConfig.hasOwnProperty(key)) {
                    return;
                }

                if (!value) {
                    return;
                }

                const labels = value.split(',').map(v => v.trim());
                const idList = [];

                if (this.facets.facetConfig[key]?.hasOptions) {
                    labels.forEach(label => {
                        const option = Object.values(this.facets.facetConfig[key].options)
                            .find(opt => opt.label.toLowerCase() === label.toLowerCase());
                        if (option) {
                            idList.push(option.value);
                        }
                    });
                } else {
                    labels.forEach(val => {
                        if (val) {
                            idList.push(val);
                        }
                    });
                }

                if (idList.length > 0) {
                    restoredFacets[key] = idList;
                }
            });

            facetsModel.selectedFacets({ ...restoredFacets });
            facetsModel.currentPage(restoredPage);
        }
    });
});
