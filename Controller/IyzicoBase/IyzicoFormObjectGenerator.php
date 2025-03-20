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

use Iyzico\Iyzipay\Helper\IyzicoHelper;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;

class IyzicoFormObjectGenerator
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new IyzicoHelper();
    }

    public function generateBuyer($checkoutSession, $guestEmail)
    {
        $billingAddress = $checkoutSession->getBillingAddress();
        $billingStreet = false;
        foreach ($billingAddress->getStreet() as $key => $street) {
            if ($street) {
                $billingStreet .= $street . ' ';
            }
        }

        if ($billingAddress->getEmail()) {
            $email = $billingAddress->getEmail();
        } else {
            $email = $guestEmail;
        }

        $buyer = new Buyer();

        $buyer->setId($billingAddress->getId());
        $buyer->setName($this->helper->dataCheck($billingAddress->getName()));
        $buyer->setSurname($this->helper->dataCheck($billingAddress->getName()));
        $buyer->setIdentityNumber("11111111111");
        $buyer->setEmail($this->helper->dataCheck($email));
        $buyer->setGsmNumber($this->helper->dataCheck($billingAddress->getTelephone()));
        $buyer->setRegistrationDate("2018-07-06 11:11:11");
        $buyer->setLastLoginDate("2018-07-06 11:11:11");
        $buyer->setRegistrationAddress($this->helper->dataCheck($billingStreet));
        $buyer->setCity($this->helper->dataCheck($billingAddress->getCity()));
        $buyer->setCountry($this->helper->dataCheck($billingAddress->getCountry()));
        $buyer->setZipCode($this->helper->dataCheck($billingAddress->getPostCode()));
        $buyer->setIp($_SERVER['REMOTE_ADDR']);

        return $buyer;
    }

    public function generateShippingAddress($checkoutSession)
    {
        $shippingAddress = $checkoutSession->getShippingAddress();
        $shippingStreet = false;
        foreach ($shippingAddress->getStreet() as $key => $street) {
            if ($street) {
                $shippingStreet .= $street . ' ';
            }
        }

        $address = new Address();

        $address->setAddress($this->helper->dataCheck($shippingStreet));
        $address->setZipCode($this->helper->dataCheck($shippingAddress->getPostCode()));
        $address->setContactName($this->helper->dataCheck($shippingAddress->getName()));
        $address->setCity($this->helper->dataCheck($shippingAddress->getCity()));
        $address->setCountry($this->helper->dataCheck($shippingAddress->getCountry()));

        return $address;
    }

    public function generateBillingAddress($checkoutSession)
    {

        $billingAddress = $checkoutSession->getBillingAddress();
        $billingStreet = false;
        foreach ($billingAddress->getStreet() as $key => $street) {
            if ($street) {
                $billingStreet .= $street . ' ';
            }
        }

        $address = new Address();

        $address->setAddress($this->helper->dataCheck($billingStreet));
        $address->setZipCode($this->helper->dataCheck($billingAddress->getPostCode()));
        $address->setContactName($this->helper->dataCheck($billingAddress->getName()));
        $address->setCity($this->helper->dataCheck($billingAddress->getCity()));
        $address->setCountry($this->helper->dataCheck($billingAddress->getCountry()));

        return $address;
    }

    public function generateBasketItems($checkoutSession)
    {

        $basketItems = array();

        foreach ($checkoutSession->getAllVisibleItems() as $key => $item) {
            $basketItem = new BasketItem();
            $basketItem->setId($item->getProductId());
            $basketItem->setPrice($this->helper->priceParser(round($item->getPrice(), 2)));
            $basketItem->setName($this->helper->dataCheck($item->getName()));
            $basketItem->setCategory1($this->helper->dataCheck($item->getName()));
            $basketItem->setItemType(BasketItemType::PHYSICAL);

            $basketItems[] = $basketItem;
        }

        $shipping = $checkoutSession->getShippingAddress()->getShippingAmount();
        if ($shipping && $shipping != '0' && $shipping != '0.0' && $shipping != '0.00' && $shipping != false) {
            $shippingItem = new BasketItem();
            $shippingItem->setId("Cargo");
            $shippingItem->setPrice($this->helper->priceParser($shipping));
            $shippingItem->setName("Cargo");
            $shippingItem->setCategory1("Cargo");
            $shippingItem->setItemType(BasketItemType::PHYSICAL);

            $basketItems[] = $shippingItem;
        }

        return $basketItems;
    }
}
