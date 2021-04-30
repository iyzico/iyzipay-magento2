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

class IyzicoRequest
{
    public function iyzicoPayWithIyzicoRequest($baseUrl,$json,$authorizationData) {

        $url = $baseUrl.'/payment/pay-with-iyzico/initialize';

        return $this->curlPost($json,$authorizationData,$url);

    }

	public function iyzicoCheckoutFormDetailRequest($baseUrl,$json,$authorizationData) {

			$url = $baseUrl.'/payment/iyzipos/checkoutform/auth/ecom/detail';

		    return $this->curlPost($json,$authorizationData,$url);

	}

	public function curlPost($json,$authorizationData,$url) {

        $phpVersion = phpversion();

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		if ($json) {
		    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 150);

		curl_setopt(
		    $curl, CURLOPT_HTTPHEADER, array(
		        "Authorization: " .$authorizationData['authorization'],
		        "x-iyzi-rnd:".$authorizationData['rand_value'],
                "php-version:". $phpVersion,
		        "Content-Type: application/json",
		    )
		);

		$result = json_decode(curl_exec($curl));
		curl_close($curl);



		return $result;
	}

}
