define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return function (config) {
        var popupSelector = config.popupSelector || '';
        var triggerSelector = config.triggerSelector || '';
        var mobileMedia = config.mobileMedia || '(max-width: 767px)';
        var $popup = $(popupSelector);

        if (!popupSelector || !triggerSelector || !$popup.length) {
            return;
        }

        modal({
            type: 'popup',
            modalClass: 'ba-mobile-filter-modal',
            responsive: true,
            innerScroll: true,
            title: config.title || $t('Filter'),
            buttons: []
        }, $popup);

        $(document).on('click', triggerSelector, function (event) {
            if (!window.matchMedia(mobileMedia).matches) {
                return;
            }

            event.preventDefault();
            $popup.modal('openModal');
        });
    };
});
