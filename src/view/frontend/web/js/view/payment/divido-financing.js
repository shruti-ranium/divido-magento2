/*
 * Copyright 2016 Divido
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push({
            type:      'divido_financing',
            component: 'Divido_DividoFinancing/js/view/payment/method-renderer/divido-dividofinancing'
        });

        return Component.extend({});
    }
);
