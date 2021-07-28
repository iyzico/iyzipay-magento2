define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'mage/storage',
        'Magento_Checkout/js/model/place-order',
        'Magento_Checkout/js/model/url-builder',
        'uiComponent'
    ],
    function (Component, $, ko, additionalValidators, urlBuilder, fullscreenLoader, quote, customer, storage, placeOrderService, mageUrlBuilder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Iyzico_Iyzipay/payment/iyzipay'
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            payWithIyzico: function (){
                var quoteEmail, guestQuoteId = false;


                if(!additionalValidators.validate()) {   //Resolve checkout aggreement accept error
                    return false;
                }

                $( document ).ready(function() {

                    $("#loadingBar").show();

                    if(!customer.isLoggedIn()) {
                        quoteEmail = quote.guestEmail;
                        guestQuoteId = quote.getQuoteId();
                    }

                    $.ajax({
                        url: urlBuilder.build("Iyzico_Iyzipay/request/iyzicocheckoutform"),
                        data: {iyziQuoteEmail: quoteEmail, iyziQuoteId: guestQuoteId},
                        type: "post",
                        dataType: "html"
                    }).done(function (data) {
                        window.location.href = data;
                    });

                });
            }
        });
    }
);
