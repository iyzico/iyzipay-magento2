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

namespace Iyzico\Iyzipay\Block\Adminhtml\Order\Creditmemo;

use Iyzico\Iyzipay\Block\Adminhtml\IyzipayTotals;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class IyzipayCreditmemoTotals
 *
 * This class extends IyzipayTotals and is used to add custom totals to the creditmemo view in the admin panel.
 *
 * @package Iyzico\Iyzipay\Block\Adminhtml\Order\Creditmemo
 * @extends IyzipayTotals
 *
 * This class is used etc/di.xml
 */
class IyzipayCreditmemoTotals extends IyzipayTotals
{
    /**
     * Creditmemo
     *
     * @var Creditmemo|null
     */
    protected ?Creditmemo $_creditmemo;

    /**
     * Initialize creditmemo totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {

        parent::_initTotals();

        $this->addTotal(
            new DataObject(
                [
                    'code' => 'adjustment_positive',
                    'value' => $this->getSource()->getAdjustmentPositive(),
                    'base_value' => $this->getSource()->getBaseAdjustmentPositive(),
                    'label' => __('Adjustment Refund'),
                ]
            )
        );

        $this->addTotal(
            new DataObject(
                [
                    'code' => 'adjustment_negative',
                    'value' => $this->getSource()->getAdjustmentNegative(),
                    'base_value' => $this->getSource()->getBaseAdjustmentNegative(),
                    'label' => __('Adjustment Fee'),
                ]
            )
        );
        return $this;
    }

    /**
     * Get source
     *
     * @return Creditmemo|null
     */
    public function getSource()
    {
        return $this->getCreditmemo();
    }

    /**
     * Retrieve creditmemo model instance
     *
     * @return Creditmemo
     */
    public function getCreditmemo()
    {
        if ($this->_creditmemo === null) {
            if ($this->hasData('creditmemo')) {
                $this->_creditmemo = $this->_getData('creditmemo');
            } elseif ($this->_coreRegistry->registry('current_creditmemo')) {
                $this->_creditmemo = $this->_coreRegistry->registry('current_creditmemo');
            } elseif ($this->getParentBlock() && $this->getParentBlock()->getCreditmemo()) {
                $this->_creditmemo = $this->getParentBlock()->getCreditmemo();
            }
        }
        return $this->_creditmemo;
    }
}
