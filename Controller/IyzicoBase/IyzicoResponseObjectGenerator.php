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

namespace Iyzico\Iyzipay\Controller\IyzicoBase;

use stdClass;
use Iyzico\Iyzipay\Helper\IyzicoHelper;

class IyzicoResponseObjectGenerator 
{

	protected $helper;

	 public function __construct() {

	 	$this->helper = new IyzicoHelper();
	 }

	public function generateTokenDetailObject($conversationId,$token) {

		$tokenDetail = new stdClass();

		$tokenDetail->locale 			= "tr";
		$tokenDetail->conversationId 	= $conversationId;
		$tokenDetail->token 			= $token;

		return $tokenDetail;
		
	}


}
