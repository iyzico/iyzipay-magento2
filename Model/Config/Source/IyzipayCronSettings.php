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

namespace Iyzico\Iyzipay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class IyzipayCronSettings implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '* * * * *', 'label' => __('Every Minute')],
            ['value' => '*/3 * * * *', 'label' => __('Every 3 Minutes')],
            ['value' => '0 * * * *', 'label' => __('Every Hour')],
            ['value' => '0 */2 * * *', 'label' => __('Every 2 Hours')],
            ['value' => '0 */4 * * *', 'label' => __('Every 4 Hours')],
            ['value' => '0 */8 * * *', 'label' => __('Every 8 Hours')],
            ['value' => '0 */12 * * *', 'label' => __('Every 12 Hours')],
            ['value' => '0 0 * * *', 'label' => __('Every Day at Midnight')],
            ['value' => 'custom', 'label' => __('Custom')],
        ];
    }
}
