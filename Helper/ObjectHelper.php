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

namespace Iyzico\Iyzipay\Helper;

use Iyzico\Iyzipay\Library\Model\Address;
use Iyzico\Iyzipay\Library\Model\BasketItem;
use Iyzico\Iyzipay\Library\Model\BasketItemType;
use Iyzico\Iyzipay\Library\Model\Buyer;
use Magento\Framework\Math\Random;

class ObjectHelper
{
    public function __construct(
        private UtilityHelper $utilityHelper,
        private Random $rand
    ) {}

    public function createBasketItems($checkoutSession): array
    {
        $basketItems = [];

        /* Basket Items */
        foreach ($checkoutSession->getAllVisibleItems() as $key => $item) {
            $basketItem = new BasketItem();

            $basketItem->setId($item->getProductId());
            $basketItem->setPrice($this->utilityHelper->parsePrice(round($item->getPrice(), 2)));
            $basketItem->setName($this->utilityHelper->validateString($item->getName()));
            $basketItem->setCategory1($this->utilityHelper->validateString($item->getName()));
            $basketItem->setItemType(BasketItemType::PHYSICAL);

            $basketItems[] = $basketItem;
        }

        $shippingAddress = $checkoutSession->getShippingAddress();
        if ($shippingAddress) {
            $shipping = $shippingAddress->getShippingAmount();
            if ($shipping && $shipping != '0' && $shipping != '0.0' && $shipping != '0.00') {
                $shippingBasketItem = new BasketItem();

                $shippingBasketItem->setId("CargoId");
                $shippingBasketItem->setPrice($this->utilityHelper->parsePrice($shipping));
                $shippingBasketItem->setName("Cargo");
                $shippingBasketItem->setCategory1("Cargo");
                $shippingBasketItem->setItemType(BasketItemType::PHYSICAL);

                $basketItems[] = $shippingBasketItem;
            }
        }

        return $basketItems;
    }

    public function createBuyer($checkoutSession): Buyer
    {
        $uuid = $this->rand->getUniqueHash();
        $billingAddress = $checkoutSession->getBillingAddress();

        $name = is_null($billingAddress->getName()) ? "UNKNOWN" : $billingAddress->getName();
        $surname = is_null($billingAddress->getName()) ? "UNKNOWN" : $billingAddress->getName();
        $identityNumber = "11111111111";
        $telephone = is_null($billingAddress->getTelephone()) ? "UNKNOWN" : $billingAddress->getTelephone();
        $street = is_null($billingAddress->getStreet()) ? "UNKNOWN" : $billingAddress->getStreet();
        $city = is_null($billingAddress->getCity()) ? "UNKNOWN" : $billingAddress->getCity();
        $country = is_null($billingAddress->getCountry()) ? "UNKNOWN" : $billingAddress->getCountry();
        $zipCode = is_null($billingAddress->getPostCode()) ? "UNKNOWN" : $billingAddress->getPostCode();
        $email = is_null($billingAddress->getEmail()) ? "UNKNOWN" : $billingAddress->getEmail();

        $buyer = new Buyer();
        $buyer->setId($uuid);
        $buyer->setName($this->utilityHelper->validateString($name));
        $buyer->setSurname($this->utilityHelper->validateString($surname));
        $buyer->setIdentityNumber($identityNumber);
        $buyer->setEmail($this->utilityHelper->validateString($email));
        $buyer->setGsmNumber($this->utilityHelper->validateString($telephone));
        $buyer->setRegistrationDate("2018-07-06 11:11:11");
        $buyer->setLastLoginDate("2018-07-06 11:11:11");
        $buyer->setRegistrationAddress($this->utilityHelper->validateString($street));
        $buyer->setCity($this->utilityHelper->validateString($city));
        $buyer->setCountry($this->utilityHelper->validateString($country));
        $buyer->setZipCode($this->utilityHelper->validateString($zipCode));
        $buyer->setIp($_SERVER['REMOTE_ADDR'] ?? "127.0.0.1");

        return $buyer;
    }

    public function createShippingAddress($checkoutSession): Address
    {
        $checkoutShippingAddress = $checkoutSession->getShippingAddress();
        $street = is_null($checkoutShippingAddress->getStreet()) ? "UNKNOWN" : $checkoutShippingAddress->getStreet();
        $zipCode = is_null($checkoutShippingAddress->getPostCode()) ? "UNKNOWN" : $checkoutShippingAddress->getPostCode();
        $contactName = is_null($checkoutShippingAddress->getName()) ? "UNKNOWN" : $checkoutShippingAddress->getName();
        $city = is_null($checkoutShippingAddress->getCity()) ? "UNKNOWN" : $checkoutShippingAddress->getCity();
        $country = is_null($checkoutShippingAddress->getCountry()) ? "UNKNOWN" : $checkoutShippingAddress->getCountry();


        $shippingAddress = new Address();
        $shippingAddress->setAddress($this->utilityHelper->validateString($street));
        $shippingAddress->setZipCode($this->utilityHelper->validateString($zipCode));
        $shippingAddress->setContactName($this->utilityHelper->validateString($contactName));
        $shippingAddress->setCity($this->utilityHelper->validateString($city));
        $shippingAddress->setCountry($this->utilityHelper->validateString($country));

        return $shippingAddress;
    }

    public function createBillingAddress($checkoutSession): Address
    {
        $checkoutBillingAddress = $checkoutSession->getBillingAddress();
        $street = is_null($checkoutBillingAddress->getStreet()) ? "UNKNOWN" : $checkoutBillingAddress->getStreet();
        $zipCode = is_null($checkoutBillingAddress->getPostCode()) ? "UNKNOWN" : $checkoutBillingAddress->getPostCode();
        $contactName = is_null($checkoutBillingAddress->getName()) ? "UNKNOWN" : $checkoutBillingAddress->getName();
        $city = is_null($checkoutBillingAddress->getCity()) ? "UNKNOWN" : $checkoutBillingAddress->getCity();
        $country = is_null($checkoutBillingAddress->getCountry()) ? "UNKNOWN" : $checkoutBillingAddress->getCountry();


        $billingAddress = new Address();
        $billingAddress->setAddress($this->utilityHelper->validateString($street));
        $billingAddress->setZipCode($this->utilityHelper->validateString($zipCode));
        $billingAddress->setContactName($this->utilityHelper->validateString($contactName));
        $billingAddress->setCity($this->utilityHelper->validateString($city));
        $billingAddress->setCountry($this->utilityHelper->validateString($country));

        return $billingAddress;
    }
}
