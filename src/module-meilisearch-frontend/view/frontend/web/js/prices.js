define([
    'jquery',
    'mage/url',
    'underscore'
], function ($, urlBuilder, _) {
    'use strict';

    function fetchPricesBySkus(skus) {
        return $.ajax({
            url: urlBuilder.build('meilisearch/ajax/prices'),
            method: 'POST',
            data: { skus: skus }
        });
    }

    function injectPrices() {
        const $hosts = $('.price-box-host');
        if (!$hosts.length) return;

        const skus = $hosts.map(function() {
            return this.dataset.sku;
        }).get().filter(Boolean);

        if (!skus.length) return;

        fetchPricesBySkus(skus).done(function (res) {
            if (!res || !res.prices) return;

            $hosts.each(function() {
                const sku = this.dataset.sku;
                const html = res.prices[sku] || '';
                if (!html) return;

                this.innerHTML = html;
            });
        });
    }

    const scheduleInject = _.debounce(injectPrices, 100);

    return {
        scheduleInject: scheduleInject
    };
});
