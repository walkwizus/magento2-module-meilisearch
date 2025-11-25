define([
    'jquery',
    'mage/url',
    'underscore'
], function ($, urlBuilder, _) {
    'use strict';

    function fetchFragmentsBySkus(skus, fragments) {
        return $.ajax({
            url: urlBuilder.build('meilisearch/ajax/fragment'),
            method: 'POST',
            data: {
                skus: skus,
                fragments: fragments
            }
        });
    }

    function injectFragments() {
        let $hosts = $('[data-fragment][data-sku]');
        if (!$hosts.length) {
            return;
        }

        let skusSet = new Set();
        let fragsSet = new Set();

        $hosts.each(function () {
            let sku = this.getAttribute('data-sku');
            let frag = this.getAttribute('data-fragment');

            if (sku) {
                skusSet.add(sku);
            }

            if (frag) {
                fragsSet.add(frag);
            }
        });

        let skus = Array.from(skusSet);
        let fragments = Array.from(fragsSet);

        if (!skus.length) {
            return;
        }

        fetchFragmentsBySkus(skus, fragments).done(function (res) {
            if (!res) return;

            fragments.forEach(function (frag) {
                let bySku = res[frag] || {};
                $hosts.filter('[data-fragment="' + frag + '"]').each(function () {
                    let sku = this.getAttribute('data-sku');
                    let html = bySku[sku] || '';

                    if (!html) {
                        return;
                    }

                    $(this).html(html).trigger('contentUpdated');
                });
            });
        });
    }

    const scheduleInject = _.debounce(injectFragments, 5);

    return {
        scheduleInject: scheduleInject
    };
});
