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
                type: 'iyzipay',
                component: 'Iyzico_Iyzipay/js/view/payment/method-renderer/iyzipay-method'
            }
        );

        return Component.extend({});
    }
);
