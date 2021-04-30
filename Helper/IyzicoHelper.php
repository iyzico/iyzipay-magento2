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

namespace Iyzico\PayWithIyzico\Helper;

class IyzicoHelper
{


	public function subTotalPriceCalc($customerSession) {

		$keyNumber 	= 0;
		$price 		= 0;

		$basketItems = $customerSession->getAllVisibleItems();

		foreach ($basketItems as  $item) {

			$price+= round($item->getPrice(), 2);

			$keyNumber++;

		}

		$shipping = $customerSession->getShippingAddress()->getShippingAmount();

		if($shipping) {

			$price+= $shipping;
		}

		$price = $this->priceParser($price);

		return $price;

	}

	public function cutLocale($locale) {

		$locale = explode('_',$locale);
		$locale = $locale[0];

		return $locale;
	}

	public function priceParser($price) {

	    if (strpos($price, ".") === false) {
	        return $price . ".0";
	    }
	    $subStrIndex = 0;
	    $priceReversed = strrev($price);
	    for ($i = 0; $i < strlen($priceReversed); $i++) {
	        if (strcmp($priceReversed[$i], "0") == 0) {
	            $subStrIndex = $i + 1;
	        } else if (strcmp($priceReversed[$i], ".") == 0) {
	            $priceReversed = "0" . $priceReversed;
	            break;
	        } else {
	            break;
	        }
	    }

	    return strrev(substr($priceReversed, $subStrIndex));
	}


	public function trimString($address1,$address2) {

		$address = trim($address1)." ".trim($address2);

		return $address;
	}


	public function dataCheck($data) {

        if(!$data || $data == ' ') {

            $data = "NOT PROVIDED";
        }

        return $data;

	}

}
