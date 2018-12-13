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

/**
 * @api
 * @since 100.0.2
 */
class OverlayScript implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'bottomLeft', 'label' => __('Bottom Left')], ['value' => 'bottomRight', 'label' => __('Bottom Right')], ['value' => 'hidden', 'label' => __('Hidden')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['bottomLeft' => __('Bottom Left'), 'bottomRight' => __('Bottom Right'), 'hidden' => __('Hidden')];
    }
}
