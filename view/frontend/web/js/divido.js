define([
    'jquery', 
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('divido.calculator', {
        _create: function () {
            var divido_calc = new Divido(this.element[0]);
        }
    });

    return $.divido.calculator;
});
