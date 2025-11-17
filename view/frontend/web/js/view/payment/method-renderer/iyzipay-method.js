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
            isPlaceOrderActionAllowed: ko.observable(true),
            paymentInitialized: ko.observable(false)
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },

        initializePaymentForm: function () {
            var self = this;
            
            // Only initialize once
            if (self.paymentInitialized()) {
                return;
            }
            
            // Wait for payment method to be selected
            var checkPaymentSelected = setInterval(function() {
                if (self.isChecked() && !self.paymentInitialized()) {
                    clearInterval(checkPaymentSelected);
                    self.paymentInitialized(true);
                    self.loadPaymentForm();
                }
            }, 500);
            
            // Clear interval after 30 seconds to prevent infinite loop
            setTimeout(function() {
                clearInterval(checkPaymentSelected);
            }, 30000);
        },

        loadPaymentForm: function () {
            var self = this;
            var quoteEmail, guestQuoteId = false;

            if (!customer.isLoggedIn()) {
                quoteEmail = quote.guestEmail;
                guestQuoteId = quote.getQuoteId();
            }

            $("#loadingBar").show();

            $.ajax({
                url: urlBuilder.build("iyzico/request/iyzipayrequest"),
                data: {
                    iyziQuoteEmail: quoteEmail,
                    iyziQuoteId: guestQuoteId
                },
                type: "post",
                dataType: "json",
                success: function (response) {
                    console.log("iyzipay response: ", response);
                    console.log("displayType: ", response.displayType);
                    console.log("checkoutFormContent exists: ", !!response.checkoutFormContent);
                    
                    if (response.success) {
                        // Check display type - support both 'iframe' string and any truthy value
                        if ((response.displayType === 'iframe' || response.displayType === '1') && response.checkoutFormContent) {
                            console.log("Showing iframe checkout form");
                            // Show iframe/checkout form
                            self.showIframeCheckoutForm(response.checkoutFormContent);
                            // Hide pay button for iframe mode
                            $('.action.primary.checkout').closest('.actions-toolbar').hide();
                        } else {
                            console.log("Redirect mode - showing pay button");
                            // Redirect mode - show pay button
                            $('.action.primary.checkout').closest('.actions-toolbar').show();
                            self.isPlaceOrderActionAllowed(true);
                        }
                    } else {
                        self.showError(response.message || 'Unknown error occurred. Please try again later.');
                        self.isPlaceOrderActionAllowed(true);
                    }
                    $("#loadingBar").hide();
                },
                error: function (xhr, status, error) {
                    console.error("AJAX error: ", error);
                    self.showError('Unknown error occurred. Please try again later.');
                    $("#loadingBar").hide();
                    self.isPlaceOrderActionAllowed(true);
                }
            });
        },

        showError: function (message) {
            messageList.addErrorMessage({
                message: message,
                sticky: true
            });
            $("#loadingBar").hide();
        },

        showIframeCheckoutForm: function (checkoutFormContent) {
            var self = this;
            console.log("showIframeCheckoutForm called with content length:", checkoutFormContent ? checkoutFormContent.length : 0);
            
            // Hide loading bar
            $("#loadingBar").hide();
            
            // Remove existing container if any
            $('#iyzipay-checkout-form-container').remove();
            
            // Create new container
            var container = $('<div id="iyzipay-checkout-form-container" style="margin-top: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 4px; background: #fff; min-height: 400px;"></div>');
            $('#iyzipay-payment').after(container);
            
            // Create checkout form div first (required by iyzico)
            var checkoutFormDiv = $('<div id="iyzipay-checkout-form" class="responsive"></div>');
            container.append(checkoutFormDiv);
            
            // Append the checkout form content (which includes scripts)
            // Parse HTML and execute scripts properly
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = checkoutFormContent;
            
            // Extract and execute scripts first
            var scripts = tempDiv.querySelectorAll('script');
            var scriptPromises = [];
            
            scripts.forEach(function(oldScript) {
                var newScript = document.createElement('script');
                if (oldScript.src) {
                    // For external scripts, create a promise to track loading
                    var scriptPromise = new Promise(function(resolve, reject) {
                        newScript.src = oldScript.src;
                        newScript.async = false;
                        newScript.onload = function() {
                            console.log("Script loaded:", oldScript.src);
                            resolve();
                        };
                        newScript.onerror = function() {
                            console.error("Script failed to load:", oldScript.src);
                            // Try to load anyway - CSP might block but we continue
                            resolve();
                        };
                    });
                    scriptPromises.push(scriptPromise);
                    document.head.appendChild(newScript);
                } else {
                    // Inline scripts - execute directly
                    newScript.text = oldScript.text || oldScript.innerHTML;
                    document.head.appendChild(newScript);
                }
            });
            
            // Move all non-script nodes to container
            var node = tempDiv.firstChild;
            while (node) {
                var nextNode = node.nextSibling;
                if (node.tagName !== 'SCRIPT') {
                    container[0].appendChild(node);
                }
                node = nextNode;
            }
            
            // Wait for scripts to load (or timeout)
            Promise.all(scriptPromises).then(function() {
                console.log("All scripts loaded");
            }).catch(function(error) {
                console.error("Script loading error:", error);
            });
            
            // Scroll to the form
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: container.offset().top - 100
                }, 500);
            }, 100);
        },

        payWithIyzico: function () {
            var self = this;
            
            // This function is only used for redirect mode
            // For iframe mode, payment form is loaded automatically
            
            if (!additionalValidators.validate()) {
                return false;
            }

            self.isPlaceOrderActionAllowed(false);
            $("#loadingBar").show();

            var quoteEmail, guestQuoteId = false;
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
                    console.log("iyzipay redirect response: ", response);
                    if (response.success) {
                        // Always redirect in this function (redirect mode)
                        console.log("Redirecting to payment page");
                        window.location.href = response.url;
                    } else {
                        self.showError(response.message || 'Unknown error occurred. Please try again later.');
                        self.isPlaceOrderActionAllowed(true);
                        $("#loadingBar").hide();
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
