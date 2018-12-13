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

class IyzicoFormObjectGenerator 
{
	 protected $helper;

	 public function __construct() {

	 	$this->helper = new IyzicoHelper();
	 }

	public function generateOption($checkoutSession,$cardUserKey,$locale,$currency,$cardId,$callBack,$magentoVersion) {

		$iyzico = new stdClass();
		$helper = new IyzicoHelper();

		$iyzico->locale                       = $this->helper->cutLocale($locale);
		$iyzico->conversationId               = "123456789";
		$iyzico->price                        = $this->helper->subTotalPriceCalc($checkoutSession);
		$iyzico->paidPrice                    = $this->helper->priceParser(round($checkoutSession->getGrandTotal(),2));
		$iyzico->currency                     = $currency;
		$iyzico->basketId                     = $cardId;
		$iyzico->paymentGroup                 = 'PRODUCT';
		$iyzico->forceThreeDS                 = "0";
		$iyzico->callbackUrl                  = $callBack."Iyzico_Iyzipay/response/iyzicocheckoutform";
		$iyzico->cardUserKey                  = $cardUserKey;
		$iyzico->paymentSource                = "MAGENTO2|".$magentoVersion."|SPACE-1.0";
		
		return $iyzico;

	}

	public function generateBuyer($checkoutSession,$guestEmail) {

		$billingAddress  = $checkoutSession->getBillingAddress();

		$billingStreet = false;
        foreach ($billingAddress->getStreet() as $key => $street) {

            if($street)
                $billingStreet.= $street.' ';
        }

        if($billingAddress->getEmail()){

        	$email = $billingAddress->getEmail();
        
        } else {
  
        	$email = $guestEmail;
        }
        
		$buyer = new stdClass();

        $buyer->id                          = $billingAddress->getId();
        $buyer->name                        = $this->helper->dataCheck($billingAddress->getName());
        $buyer->surname                     = $this->helper->dataCheck($billingAddress->getName());
        $buyer->identityNumber              = "11111111111";   
        $buyer->email                       = $this->helper->dataCheck($email);  
        $buyer->gsmNumber                   = $this->helper->dataCheck($billingAddress->getTelephone());  
        $buyer->registrationDate            = "2018-07-06 11:11:11";
        $buyer->lastLoginDate               = "2018-07-06 11:11:11";
        $buyer->registrationAddress         = $this->helper->dataCheck($billingStreet);   
        $buyer->city                        = $this->helper->dataCheck($billingAddress->getCity());
        $buyer->country                     = $this->helper->dataCheck($billingAddress->getCountry());    
        $buyer->zipCode                     = $this->helper->dataCheck($billingAddress->getPostCode());  
        $buyer->ip                          = $_SERVER['REMOTE_ADDR'];  

        return $buyer;
	}

	public function generateShippingAddress($checkoutSession) {

		$shippingAddress  = $checkoutSession->getShippingAddress();

        $shippingStreet = false;
        foreach ($shippingAddress->getStreet() as $key => $street) {

            if($street)
                $shippingStreet.= $street.' ';
        }

		$shippingAddressObj = new stdClass();

		$shippingAddressObj->address          = $this->helper->dataCheck($shippingStreet);
		$shippingAddressObj->zipCode          = $this->helper->dataCheck($shippingAddress->getPostCode());
		$shippingAddressObj->contactName      = $this->helper->dataCheck($shippingAddress->getName());
		$shippingAddressObj->city             = $this->helper->dataCheck($shippingAddress->getCity());
		$shippingAddressObj->country          = $this->helper->dataCheck($shippingAddress->getCountry());

		return $shippingAddressObj;

	}

	public function generateBillingAddress($checkoutSession) {

		$billingAddress  = $checkoutSession->getBillingAddress();

		$billingStreet = false;
        foreach ($billingAddress->getStreet() as $key => $street) {

            if($street)
                $billingStreet.= $street.' ';
        }

		$billingAddressObj = new stdClass();

		$billingAddressObj->address          = $this->helper->dataCheck($billingStreet);
		$billingAddressObj->zipCode          = $this->helper->dataCheck($billingAddress->getPostCode()); 
		$billingAddressObj->contactName      = $this->helper->dataCheck($billingAddress->getName());
		$billingAddressObj->city             = $this->helper->dataCheck($billingAddress->getCity());
		$billingAddressObj->country          = $this->helper->dataCheck($billingAddress->getCountry());    

		return $billingAddressObj;
	}

	public function generateBasketItems($checkoutSession) {

		$basketItems = $checkoutSession->getAllVisibleItems();

		$keyNumber = 0;

        /* Basket Items */
        foreach($basketItems as $key => $item) {

            $basketItems[$keyNumber] = new stdClass();

            $basketItems[$keyNumber]->id                = $item->getProductId();
            $basketItems[$keyNumber]->price             = $this->helper->priceParser(round($item->getPrice(),2));
            $basketItems[$keyNumber]->name              = $this->helper->dataCheck($item->getName());
            $basketItems[$keyNumber]->category1         = "MAGENTO-ECOMMERCE";
            $basketItems[$keyNumber]->itemType          = "PHYSICAL";

            $keyNumber++;
        }

        $shipping = $checkoutSession->getShippingAddress()->getShippingAmount();

		if($shipping && $shipping != '0' && $shipping != '0.0' && $shipping != '0.00' && $shipping != false) {

			$endKey = count($basketItems);

			$basketItems[$endKey] = new stdClass();

			$basketItems[$endKey]->id                = rand();
			$basketItems[$endKey]->price             = $this->helper->priceParser($shipping);
			$basketItems[$endKey]->name              = "Cargo";
			$basketItems[$endKey]->category1         = "Cargo";
			$basketItems[$endKey]->itemType          = "PHYSICAL";

		}

        return $basketItems;
	}




}