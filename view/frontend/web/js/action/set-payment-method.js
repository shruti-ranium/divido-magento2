define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Customer/js/model/customer'
    ],
    function ($, quote, urlBuilder, storage, customer) {
        'use strict';

        return function (messageContainer) {
            var serviceUrl,
                creditRequestUrl,
                payload,
                paymentData = quote.paymentMethod();

            paymentData = {"method": paymentData.method};

            /**
             * Checkout for guest and registered customer.
             */
            if (!customer.isLoggedIn()) {
                serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/selected-payment-method', {
                    cartId: quote.getQuoteId()
                });
                payload = {
                    cartId: quote.getQuoteId(),
                    method: paymentData
                };
            } else {
                serviceUrl = urlBuilder.createUrl('/carts/mine/selected-payment-method', {});
                payload = {
                    cartId: quote.getQuoteId(),
                    method: paymentData
                };
            }

            return storage.put(serviceUrl, JSON.stringify(payload));
        };
    }
);
