define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list',
        'mage/url'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'paywithiyzico',
                component: 'Iyzico_PayWithIyzico/js/view/payment/method-renderer/paywithiyzico-method'
            }
        );

        return Component.extend({});
    }
);
