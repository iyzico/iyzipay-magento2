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

namespace Iyzico\Iyzipay\Block\Adminhtml;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Totals;
use Magento\Sales\Helper\Admin;

/**
 * Class IyzipayTotals
 *
 * This class extends Totals and is used to create a custom totals block for the iyzico payment gateway
 *
 * @package Iyzico\Iyzipay\Block\Adminhtml
 * @extends Totals
 *
 * This class is used in etc/di.xml
 */
class IyzipayTotals extends Totals
{
    /**
     * Admin Helper
     *
     * @var Admin
     */
    protected Admin $_adminHelper;

    /**
     * IyzipayTotals constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        Context  $context,
        Registry $registry,
        Admin    $adminHelper,
        array    $data = []
    )
    {
        $this->_adminHelper = $adminHelper;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Format total value based on order currency
     *
     * @param DataObject $total
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

        if ((double)$this->getSource()->getOrder()->getInstallmentFee() != 0) {
            $this->_totals['installment_fee'] = new DataObject(
                [
                    'code' => 'installment_fee',
                    'value' => $this->getSource()->getOrder()->getInstallmentFee(),
                    'base_value' => $this->getSource()->getOrder()->getInstallmentFee(),
                    'label' => $this->getSource()->getOrder()->getInstallmentCount() . ' ' . __('Installment'),
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
        if (!$this->getSource()->getIsVirtual() && ((double)$this->getSource()->getShippingAmount() ||
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
        if ((double)$this->getSource()->getDiscountAmount() != 0) {
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

        return $this;
    }
}
