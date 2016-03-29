define(
    [
        'jquery',
        'Magento_Checkout/js/model/url-builder',
    ],
    function($, urlBuilder) {
        return function (quote) {
            creditRequestUrl = '/rest/V1/divido/credit-request/' + quote.getQuoteId();

            return $.ajax({
                url: creditRequestUrl,
                type: 'GET',
                global: true,
                contentType: 'application/json'
            });
		};
    }
);
