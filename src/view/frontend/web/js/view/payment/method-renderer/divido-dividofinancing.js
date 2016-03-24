define(
    [
		'jquery',
        'Magento_Checkout/js/view/payment/default',
		'Magento_Checkout/js/model/quote',
        'Divido_DividoFinancing/js/action/set-payment-method'

    ],
    function ($, Component, quote, setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Divido_DividoFinancing/payment/form',
                transactionResult: ''
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'transactionResult'
                    ]);

                return this;
            },

            getCode: function () {
                return 'divido_financing';
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'transaction_result': this.transactionResult()
                    }
                }
            },

			getTransactionResults: function() {
				return _.map(window.checkoutConfig.payment.divido_financing.transactionResults, function(value, key) {
					return {
						'value':              key,
						'transaction_result': value
					}
				});
			},

            continueToDivido: function () {
                setPaymentMethodAction(this.messageContainer);
            }
        });

    }
);
