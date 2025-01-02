<?php

namespace Iyzico\Iyzipay\Plugin\Order\Email\Container;

use Magento\Sales\Model\Order\Email\Container\OrderIdentity;

class IyzipayOrderIdentity
{
    /**
     * Order e-mail sending disable for Iyzico payment method
     *
     * @param  OrderIdentity  $subject
     * @param  bool  $result
     * @return bool
     */
    public function afterIsEnabled(
        OrderIdentity $subject,
        $result
    ) {
        $order = $subject->getOrder();

        if (!$order) {
            return $result;
        }

        $paymentMethod = $order->getPayment()->getMethod();
        $blockedMethods = ['iyzipay'];

        $blockedStatuses = ['pending_payment', 'received'];

        if (in_array($paymentMethod, $blockedMethods, true) &&
            in_array($order->getStatus(), $blockedStatuses, true)) {
            $result = false;
        }

        return $result;
    }
}
