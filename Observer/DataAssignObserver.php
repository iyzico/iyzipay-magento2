<?php
/**
 * iyzico Payment Gateway For Magento 2
 * Copyright (C) 2018 iyzico
 *
 * This file is part of Iyzico/Iyzipay.
 *
 * Iyzico/Iyzipay is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Iyzico\Iyzipay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DataAssignObserver implements ObserverInterface
{
    /**
     * Execute observer
     *
     * This method is called when the event specified in the events.xml file is triggered.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $paymentMethod = $order->getPayment()->getMethod();
        $installmentCount = $order->getInstallmentCount();

        if (isset($installmentCount) && $installmentCount > 1 && $paymentMethod == 'iyzipay') {

            $getInstallmentFee = $order->getInstallmentFee();
            $installmentCount = $order->getInstallmentCount();
            $grandTotalWithFee = $order->getGrandTotal();
            $subTotalWithFee = $order->getSubTotal();
            $currency = $order->getOrderCurrencyCode();

            $installmentCount = !empty($installmentCount) ? $installmentCount : 1;
            $getInstallmentFee = !empty($getInstallmentFee) ? $getInstallmentFee : '0.00';
            $grandTotalWithFee = !empty($grandTotalWithFee) ? $grandTotalWithFee : 0.00;
            $subTotalWithFee = !empty($subTotalWithFee) ? $subTotalWithFee : 0.00;
            $currency = !empty($currency) ? $currency : 'N/A';

            $order->setBaseTotalPaid($grandTotalWithFee);
            $order->setTotalPaid($grandTotalWithFee);
            $order->setSubTotalInvoiced($subTotalWithFee);
            $order->setBaseSubTotalInvoiced($subTotalWithFee);
            $order->setBaseTotalDue(0);
            $order->setTotalDue(0);

            $payment = $order->getPayment();
            $payment->setBaseAmountPaid($grandTotalWithFee);
            $payment->setAmountPaid($grandTotalWithFee);

            $installmentInfo = sprintf(__('Installment Info: %d Installment / %s %s'), $installmentCount, $getInstallmentFee, $currency);

            $order->addStatusHistoryComment($installmentInfo)->setIsVisibleOnFront(true);
        }
    }
}
