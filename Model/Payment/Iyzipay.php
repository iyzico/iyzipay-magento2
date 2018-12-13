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

namespace Iyzico\Iyzipay\Model\Payment;

class Iyzipay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PLUGIN_VERSION = '1.0.0';
    protected $_code = "iyzipay";
    protected $_isOffline = true;
   
   public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {

        return parent::isAvailable($quote);
    }
   
}
