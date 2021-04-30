<?php
/**
 * iyzico Payment Gateway For Magento 2
 * Copyright (C) 2018 iyzico
 *
 * This file is part of Iyzico/PayWithIyzico.
 *
 * Iyzico/PayWithIyzico is free software: you can redistribute it and/or modify
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

namespace Iyzico\PayWithIyzico\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Sales\Model\Order;



class PayWithIyzicoDataAssignObserver implements \Magento\Framework\Event\ObserverInterface {
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
    */
    protected $_objectManager;
    protected $_orderFactory;
    protected $_checkoutSession;


    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        if($observer->getEvent()->getOrder()->getPayment()->getMethodInstance()->getCode() == 'paywithiyzico'){


            $paymentId = $this->_checkoutSession->getQuote()->getIyzicoPaymentId();
            $PayWithIyzicoPaymentStatus = $this->_checkoutSession->getQuote()->getPayWithIyzicoPaymentStatus();

            $order = $observer->getEvent()->getOrder();

            if($PayWithIyzicoPaymentStatus == 'pending') {
                $order->setState('pending');
                $order->setStatus('pending');
                $bankTransferComment = __('Bank Transfer is waiting.');

                $order->addStatusHistoryComment($bankTransferComment)->setIsVisibleOnFront(true);

            }

            if($PayWithIyzicoPaymentStatus == 'success') {

                /* Create Invoice With Installment */

                if($order->getInstallmentFee()) {

                    $grandTotalWithFee = $order->getGrandTotal();
                    $subTotalWithFee = $order->getSubTotal();


                    $order->setBaseTotalPaid($grandTotalWithFee);
                    $order->setTotalPaid($grandTotalWithFee);
                    $order->setSubTotalInvoiced($subTotalWithFee);
                    $order->setBaseSubTotalInvoiced($subTotalWithFee);
                    $order->setBaseTotalDue(0);
                    $order->setBaseTotalDue(0);


                    $payment = $order->getPayment();
                    $payment->setBaseAmountPaid($grandTotalWithFee);
                    $payment->setAmountPaid($grandTotalWithFee);

                }

                /* Create Order With Installment */
                $installmentFee = $this->_checkoutSession->getQuote()->getInstallmentFee();
                $installmentCount = $this->_checkoutSession->getQuote()->getInstallmentCount();
                $grandTotal     = $this->_checkoutSession->getQuote()->getGrandTotal();
                $subTotal       = $this->_checkoutSession->getQuote()->getSubtotal();
                $iyziCurrency   = $this->_checkoutSession->getQuote()->getIyziCurrency();

                if($installmentFee && $grandTotal) {

                    $grandTotal+= $installmentFee;
                    $subTotal+= $installmentFee;
                    $iyzicoGrandTotal = $grandTotal;
                    $iyzicoSubTotal = $subTotal;

                    $order->setInstallmentFee($installmentFee);
                    $order->setInstallmentCount($installmentCount);
                    $order->setSubTotal($iyzicoSubTotal);
                    $order->setBaseSubTotal($iyzicoSubTotal);
                    $order->setSubTotalInclTax($iyzicoSubTotal);
                    $order->setGrandTotal($iyzicoGrandTotal);
                    $order->setBaseGrandTotal($iyzicoGrandTotal);

                    $payment = $order->getPayment();
                    $payment->setAmountOrdered($iyzicoGrandTotal);
                    $payment->setBaseAmountOrdered($iyzicoGrandTotal);

                    /* Add Installment Info */
                    $installmentInfo = __('Installment Info:').$installmentCount.
                                       __('Installment').' / '.$installmentFee
                                       .' '.$iyziCurrency;

                    $order->addStatusHistoryComment($installmentInfo)->setIsVisibleOnFront(true);

                }

                $order->setState('processing');
                $order->setStatus('processing');

                $historyComment = __('Payment Success').$paymentId;
                $order->addStatusHistoryComment($historyComment);
            }
        }
    }
}
