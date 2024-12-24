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

namespace Iyzico\Iyzipay\Block\Adminhtml\Order;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Totals;
use Magento\Sales\Helper\Admin;

/**
 * Class IyzipayOrderTotals
 *
 * This class extends Totals and is used to add custom totals to the order view in the admin panel.
 *
 * @package Iyzico\Iyzipay\Block\Adminhtml\Order
 * @extends Totals
 *
 * This class is used etc/di.xml
 */
class IyzipayOrderTotals extends Totals
{
    /**
     * Admin Helper
     *
     * @var Admin
     */
    protected Admin $_adminHelper;

    /**
     * IyzipayOrderTotals constructor
     *
     * @param  Context  $context
     * @param  Registry  $registry
     * @param  Admin  $adminHelper
     * @param  array  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        array $data = []
    ) {
        $this->_adminHelper = $adminHelper;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Format total value based on order currency
     *
     * @param  DataObject  $total
     * @return string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->_adminHelper->displayPrices($this->getOrder(), $total->getBaseValue(), $total->getValue());
        }
        return $total->getValue();
    }

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $this->_totals = [];

        /**
         * Add Installment Fee
         */
        if ((double) $this->getSource()->getInstallmentFee() != 0) {
            $this->_totals['installment_fee'] = new DataObject(
                [
                    'code' => 'installment_fee',
                    'value' => $this->getSource()->getInstallmentFee(),
                    'base_value' => $this->getSource()->getInstallmentFee(),
                    'label' => $this->getSource()->getInstallmentCount().' '.__('Installment'),
                ]
            );
        }

        $this->_totals['subtotal'] = new DataObject(
            [
                'code' => 'subtotal',
                'value' => $this->getSource()->getSubtotal(),
                'base_value' => $this->getSource()->getBaseSubtotal(),
                'label' => __('Subtotal'),
            ]
        );

        /**
         * Add shipping
         */
        if (!$this->getSource()->getIsVirtual() && ((double) $this->getSource()->getShippingAmount() ||
                $this->getSource()->getShippingDescription())
        ) {
            $this->_totals['shipping'] = new DataObject(
                [
                    'code' => 'shipping',
                    'value' => $this->getSource()->getShippingAmount(),
                    'base_value' => $this->getSource()->getBaseShippingAmount(),
                    'label' => __('Shipping & Handling'),
                ]
            );
        }

        /**
         * Add discount
         */
        if ((double) $this->getSource()->getDiscountAmount() != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = __('Discount (%1)', $this->getSource()->getDiscountDescription());
            } else {
                $discountLabel = __('Discount');
            }
            $this->_totals['discount'] = new DataObject(
                [
                    'code' => 'discount',
                    'value' => $this->getSource()->getDiscountAmount(),
                    'base_value' => $this->getSource()->getBaseDiscountAmount(),
                    'label' => $discountLabel,
                ]
            );
        }

        $this->_totals['grand_total'] = new DataObject(
            [
                'code' => 'grand_total',
                'strong' => true,
                'value' => $this->getSource()->getGrandTotal(),
                'base_value' => $this->getSource()->getBaseGrandTotal(),
                'label' => __('Grand Total'),
                'area' => 'footer',
            ]
        );

        $this->_totals['paid'] = new DataObject(
            [
                'code' => 'paid',
                'strong' => true,
                'value' => $this->getSource()->getTotalPaid(),
                'base_value' => $this->getSource()->getBaseTotalPaid(),
                'label' => __('Total Paid'),
                'area' => 'footer',
            ]
        );
        $this->_totals['refunded'] = new DataObject(
            [
                'code' => 'refunded',
                'strong' => true,
                'value' => $this->getSource()->getTotalRefunded(),
                'base_value' => $this->getSource()->getBaseTotalRefunded(),
                'label' => __('Total Refunded'),
                'area' => 'footer',
            ]
        );
        $this->_totals['due'] = new DataObject(
            [
                'code' => 'due',
                'strong' => true,
                'value' => $this->getSource()->getTotalDue(),
                'base_value' => $this->getSource()->getBaseTotalDue(),
                'label' => __('Total Due'),
                'area' => 'footer',
            ]
        );

        return $this;
    }
}
