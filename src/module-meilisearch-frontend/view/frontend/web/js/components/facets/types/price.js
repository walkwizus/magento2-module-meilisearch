define([
    'uiElement',
    'ko',
    'jquery',
    'noUiSlider',
    'Walkwizus_MeilisearchFrontend/js/model/facets-state'
], function(Element, ko, $, noUiSlider, facetsState) {
    'use strict';

    return Element.extend({
        initialize: function() {
            this._super();
            this.selectedFacets = facetsState.selectedFacets;
            return this;
        },

        initSlider: function(element, filter) {
            this.filter = filter;
            this.sliderElement = element;

            this.createSlider();

            this.filter.min.subscribe(this.updateSlider.bind(this));
            this.filter.max.subscribe(this.updateSlider.bind(this));

            return this;
        },

        createSlider: function() {
            const min = this.filter.min();
            const max = this.filter.max();
            const code = this.filter.code;

            if (this.slider) {
                this.slider.destroy();
            }

            this.slider = noUiSlider.create(this.sliderElement, {
                start: this.getStartValues(min, max, code),
                connect: true,
                range: { min, max },
                step: 1,
                tooltips: true,
                format: {
                    to: value => Math.round(value),
                    from: value => parseFloat(value)
                }
            });

            this.slider.on('change', (values) => {
                const from = values[0];
                const to = values[1];
                const facets = { ...this.selectedFacets() };

                if (from > min || to < max) {
                    facets[code] = [`${from}_${to}`];
                } else {
                    delete facets[code];
                }

                this.selectedFacets(facets);
            });
        },

        updateSlider: function () {
            if (!this.sliderElement || !this.filter || !this.slider) {
                return;
            }

            const min = this.filter.min();
            const max = this.filter.max();
            const code = this.filter.code;

            this.slider.updateOptions({
                range: { min, max }
            });

            const start = this.getStartValues(min, max, code);
            this.slider.set(start);
        },

        getStartValues: function(min, max, code) {
            const selected = this.selectedFacets()[code];

            if (selected && selected.length) {
                const [from, to] = selected[0].split('_').map(parseFloat);
                return [from || min, to || max];
            }

            return [min, max];
        },

        resetFilter: function () {
            const code = this.filter.code;
            const min = this.filter.min();
            const max = this.filter.max();

            if (this.slider) {
                this.slider.set([min, max]);
            }

            const facets = { ...this.selectedFacets() };
            delete facets[code];
            this.selectedFacets(facets);
        }
    });
});
