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

namespace Iyzico\Iyzipay\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Result extends Template
{
    protected $_request;
    protected $_assetRepository;
    protected $priceCurrency;

    public function __construct(
        Template\Context $context,
        RequestInterface $request,
        Repository $assetRepository,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->_request = $request;
        $this->_assetRepository = $assetRepository;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    public function formatPrice($price)
    {
        return $this->priceCurrency->format(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getData('order') ? $this->getData('order')->getStore() : null
        );
    }
}
