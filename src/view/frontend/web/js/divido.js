define([
    'jquery', 
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('divido.calculator', {
        _create: function () {
            divido_calculator(this.element[0]);
        }
    });

    return $.divido.calculator;
});
