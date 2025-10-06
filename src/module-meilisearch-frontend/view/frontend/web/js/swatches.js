define([
    'jquery',
    'mage/url',
    'underscore'
], function ($, urlBuilder, _) {
    'use strict';

    function fetchSwatchesBySkus(skus) {
        return $.ajax({
            url: urlBuilder.build('meilisearch/ajax/swatches'),
            method: 'POST',
            data: { skus: skus }
        });
    }

    function injectSwatches() {
        const $hosts = $('.swatch-host');
        if (!$hosts.length) return;

        const skus = $hosts.map(function() {
            return this.dataset.sku;
        }).get().filter(Boolean);

        if (!skus.length) return;

        fetchSwatchesBySkus(skus).done(function (res) {
            if (!res || !res.swatches) return;

            $hosts.each(function() {
                const sku = this.dataset.sku;
                const html = res.swatches[sku] || '';
                if (!html) return;

                $(this).html(html).trigger('contentUpdated');
            });
        });
    }

    const scheduleInject = _.debounce(injectSwatches, 100);

    return {
        scheduleInject: scheduleInject
    };
});
