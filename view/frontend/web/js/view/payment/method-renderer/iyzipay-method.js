define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'mage/url',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'mage/storage',
        'Magento_Checkout/js/model/place-order',
        'Magento_Checkout/js/model/url-builder',
        'uiComponent'
    ],
    function (Component, $, urlBuilder, fullscreenLoader, quote, customer, storage, placeOrderService, mageUrlBuilder) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Iyzico_Iyzipay/payment/iyzipay'
            },
            getInstructions: function () {
                return window.checkoutConfig.payment.instructions[this.item.method];
            },
            getInitIyzicoForm: function () {

                var quoteEmail, guestQuoteId, checkoutStatus = false;

                $( document ).ready(function() {

                     if(!customer.isLoggedIn()) {
                        console.log(quote.guestEmail);
                         quoteEmail = quote.guestEmail;
                         guestQuoteId = quote.getQuoteId();
                    }

                    if(checkoutStatus == false) {
                        $.ajax({
                            url: urlBuilder.build("Iyzico_Iyzipay/request/iyzicocheckoutform"),
                            data: {iyziQuoteEmail: quoteEmail, iyziQuoteId: guestQuoteId},
                            type: "post",
                            dataType: "html"
                        }).done(function (data) {
                            $("#loadingBar").hide();                           
                            $("#iyzicopaymenForm").append(data);
                            checkoutStatus = true;
                        });

                    }
                });
            }
        });
    }
);