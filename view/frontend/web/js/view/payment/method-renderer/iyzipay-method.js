define([
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
    'uiComponent',
    'Magento_Ui/js/model/messageList' // Mesaj gösterimi için eklendi
], function (
    Component,
    $,
    ko,
    additionalValidators,
    urlBuilder,
    fullscreenLoader,
    quote,
    customer,
    storage,
    placeOrderService,
    mageUrlBuilder,
    uiComponent,
    messageList
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Iyzico_Iyzipay/payment/iyzipay',
            errorMessage: ko.observable(''),
            isPlaceOrderActionAllowed: ko.observable(true)
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },

        showError: function (message) {
            messageList.addErrorMessage({
                message: message,
                sticky: true
            });
            $("#loadingBar").hide();
        },

        payWithIyzico: function () {
            var self = this;
            var quoteEmail, guestQuoteId = false;

            if (!additionalValidators.validate()) {
                return false;
            }

            self.isPlaceOrderActionAllowed(false);
            $("#loadingBar").show();

            if (!customer.isLoggedIn()) {
                quoteEmail = quote.guestEmail;
                guestQuoteId = quote.getQuoteId();
            }

            $.ajax({
                url: urlBuilder.build("iyzico/request/iyzipayrequest"),
                data: {
                    iyziQuoteEmail: quoteEmail,
                    iyziQuoteId: guestQuoteId
                },
                type: "post",
                dataType: "json",
                success: function (response) {
                    console.log("response: ", response);
                    if (response.success) {
                        window.location.href = response.url;
                    } else {
                        self.showError(response.message || 'Unknown error occurred. Please try again later.');
                        self.isPlaceOrderActionAllowed(true);
                    }
                },
                error: function (xhr, status, error) {
                    self.showError('Unknown error occurred. Please try again later.');
                    $("#loadingBar").hide();
                    self.isPlaceOrderActionAllowed(true);
                }
            });
        }
    });
});
