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

namespace Iyzico\Iyzipay\Block\Iyzipay;

use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class OverlayScript extends Template
{
    protected int $storeId;
    protected ScopeConfigInterface $scopeConfig;
    protected StoreManagerInterface $storeManager;


    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function render(): ?string
    {
        $websiteId = $this->storeManager->getWebsite()->getId();

        $position = $this->scopeConfig->getValue(
            'payment/iyzipay/overlayscript',
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        $protectedShopId = $this->scopeConfig->getValue(
            'payment/iyzipay/protectedShopId',
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        if ($position !== 'hidden') {
            return <<<HTML
                <script>
                    window.iyz = {
                        token: '{$protectedShopId}',
                        position: '{$position}',
                        ideaSoft: false
                    };
                </script>
                <script src="https://static.iyzipay.com/buyer-protection/buyer-protection.js" type="text/javascript">
                </script>
            HTML;
        }

        return null;
    }
}
