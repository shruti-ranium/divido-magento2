define(
    [
        'jquery',
        'Magento_Checkout/js/model/url-builder',
    ],
    function ($, urlBuilder) {
        return function (planId, deposit, email) {
            var creditRequestUrl = '/rest/V1/divido/credit-request/';

            var data = {
                plan   : planId,
                deposit: deposit,
                email  : email
            };

            return $.ajax({
                url: creditRequestUrl,
                type: 'GET',
                data: data,
                global: true,
                contentType: 'application/json'
            });
        };
    }
);
