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

namespace Iyzico\PayWithIyzico\Model;

class IyziCard extends \Magento\Framework\Model\AbstractModel
{

	public function __construct(
	        \Magento\Framework\Model\Context $context,
	        \Magento\Framework\Registry $registry,
	        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
	        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
	        array $data = []
	)
	{
	    parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	}

	public function _construct()
	{
	    $this->_init('Iyzico\PayWithIyzico\Model\ResourceModel\IyziCard');
	}
}
