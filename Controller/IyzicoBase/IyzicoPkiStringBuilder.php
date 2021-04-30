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

namespace Iyzico\PayWithIyzico\Controller\IyzicoBase;

use stdClass;

class IyzicoPkiStringBuilder
{

	public function pkiStringGenerate($objectData) {

		$pki_value = "[";
		foreach ($objectData as $key => $data) {

			if(is_object($data)) {

				$name = var_export($key, true);
				$name = str_replace("'", "", $name);
				$pki_value .= $name."=[";

				$end_key = count(get_object_vars($data));
				$count 	 = 0;

				foreach ($data as $key => $value) {

					$count++;
					$name = var_export($key, true);
					$name = str_replace("'", "", $name);


					$pki_value .= $name."="."".$value;

					if($end_key != $count)
						$pki_value .= ",";
				}

				$pki_value .= "]";

			} else if(is_array($data)) {
				$name = var_export($key, true);
				$name = str_replace("'", "", $name);

				$pki_value .= $name."=[";
				$end_key = count($data);
				$count 	 = 0;

				foreach ($data as $key => $result) {

					$count++;
					$pki_value .= "[";

					foreach ($result as $key => $item) {
						$name = var_export($key, true);
						$name = str_replace("'", "", $name);

						$pki_value .= $name."="."".$item;

						if(end($result) != $item) {
							$pki_value .= ",";
						}

						if(end($result) == $item) {
							if($end_key != $count) {

								$pki_value .= "], ";

							} else {

								$pki_value .= "]";
							}
						}
					}
				}

				if(end($data) == $result)
					$pki_value .= "]";

			} else {

				$name = var_export($key, true);
				$name = str_replace("'", "", $name);


				$pki_value .= $name."="."".$data."";
			}

			if(end($objectData) != $data)
				$pki_value .= ",";
		}

		$pki_value .= "]";

		return $pki_value;
	}

	public function createFormObjectSort($objectData) {


		$form_object = new stdClass();

		$form_object->locale 						= $objectData->locale;
		$form_object->conversationId 				= $objectData->conversationId;
		$form_object->price 						= $objectData->price;
		$form_object->basketId 						= $objectData->basketId;
		$form_object->paymentGroup 					= $objectData->paymentGroup;

		$form_object->buyer = new stdClass();
		$form_object->buyer = $objectData->buyer;

		$form_object->shippingAddress = new stdClass();
		$form_object->shippingAddress = $objectData->shippingAddress;

		$form_object->billingAddress = new stdClass();
		$form_object->billingAddress = $objectData->billingAddress;

		foreach ($objectData->basketItems as $key => $item) {

			$form_object->basketItems[$key] = new stdClass();
			$form_object->basketItems[$key] = $item;

		}

		$form_object->callbackUrl 			= $objectData->callbackUrl;
		$form_object->paymentSource 		= $objectData->paymentSource;
		$form_object->currency 	  			= $objectData->currency;
		$form_object->paidPrice   			= $objectData->paidPrice;
        $form_object->cancelUrl 			= $objectData->cancelUrl;

		return $form_object;
	}

	public function authorizationGenerate($pkiString,$apiKey,$secretKey,$rand) {

		$hash_value = $apiKey.$rand.$secretKey.$pkiString;
		$hash 		= base64_encode(sha1($hash_value,true));

		$authorization 	= 'IYZWS '.$apiKey.':'.$hash;

		$authorization_data = array(
			'authorization' => $authorization,
			'rand_value' 	=> $rand
		);

		return $authorization_data;
	}
}
