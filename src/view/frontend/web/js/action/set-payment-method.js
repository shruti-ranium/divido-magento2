define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Divido_DividoFinancing/js/model/credit-request'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, creditRequest) {
        'use strict';

        return function (messageContainer) {
            var serviceUrl,
                creditRequestUrl,
                payload,
                paymentData = quote.paymentMethod();

            paymentData = {"method": paymentData.method};

            creditRequest(quote)
                .done(function () {
                    console.log('done');
                })
                .fail(function () {
                    console.log('fail');
                });


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

            fullScreenLoader.startLoader();

            return storage.put(
                serviceUrl, JSON.stringify(payload)
            ).done(
                function () {
                    console.log('sparat, skickar till divido');
                    //$.mage.redirect(window.checkoutConfig.payment.paypalExpress.redirectUrl[quote.paymentMethod().method]);
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
