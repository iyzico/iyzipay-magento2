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
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;

readonly class ObjectHelper
{
    public function __construct(
        private UtilityHelper          $utilityHelper,
        private Random                 $rand,
        private CollectionFactory      $installmentCollectionFactory,
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

    /**
     * @throws LocalizedException
     */
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

    public function createAddress($address): Address
    {
        $street = is_null($address->getStreet()) ? "UNKNOWN" : $address->getStreet();
        $zipCode = is_null($address->getPostCode()) ? "UNKNOWN" : $address->getPostCode();
        $contactName = is_null($address->getName()) ? "UNKNOWN" : $address->getName();
        $city = is_null($address->getCity()) ? "UNKNOWN" : $address->getCity();
        $country = is_null($address->getCountry()) ? "UNKNOWN" : $address->getCountry();


        $shippingAddress = new Address();
        $shippingAddress->setAddress($this->utilityHelper->validateString($street));
        $shippingAddress->setZipCode($this->utilityHelper->validateString($zipCode));
        $shippingAddress->setContactName($this->utilityHelper->validateString($contactName));
        $shippingAddress->setCity($this->utilityHelper->validateString($city));
        $shippingAddress->setCountry($this->utilityHelper->validateString($country));

        return $shippingAddress;
    }

    public function getInstallment($checkoutSession): array
    {
        // Bu fonksiyon, sepetin tüm ürünlerinin kategorilerine göre taksit seçeneklerini döndürür.
        // Örneğin, bir kategoride 1,2,3,8 taksit varsa, diğer kategoride 1,2,4,6 taksit varsa,
        // Sonuç olarak 1,2,3,4,5,6 taksit seçeneklerini döndürür.
        // iyzico ayarlarında da taksit sayısı 1,2,4,6 ise
        // Ödeme formunda 1,2,4,6 taksit seçenekleri görünür.
        $allPossibleInstallments = range(1, 12);
        $categoryInstallments = [];

        foreach ($checkoutSession->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productCategories = $product->getCategoryIds();

            $productInstallments = [];
            foreach ($productCategories as $categoryId) {
                $categoryRules = $this->getInstallmentRuleByCategoryId($categoryId);
                if (!empty($categoryRules)) {
                    $productInstallments = array_merge($productInstallments, $categoryRules);
                }
            }

            if (!empty($productInstallments)) {
                $productInstallments = array_unique($productInstallments);
                sort($productInstallments);
                $categoryInstallments[] = $productInstallments;
            }
        }

        if (empty($categoryInstallments)) {
            return [];
        }

        $installmentOptions = $allPossibleInstallments;

        foreach ($categoryInstallments as $options) {
            if (empty($options)) {
                return [];
            }

            foreach ($allPossibleInstallments as $installment) {
                if (!in_array($installment, $options) && in_array($installment, $installmentOptions)) {
                    $key = array_search($installment, $installmentOptions);
                    unset($installmentOptions[$key]);
                }
            }
        }

        $installmentOptions = array_values(array_filter($installmentOptions));

        if (empty($installmentOptions)) {
            return [];
        }

        sort($installmentOptions);

        return $installmentOptions;
    }

    public function getInstallmentRuleByCategoryId($categoryId): array
    {
        $collection = $this->installmentCollectionFactory->create();
        $collection->addFieldToFilter('category_id', ['eq' => $categoryId]);

        $installmentRule = $collection->getFirstItem();

        if (!$installmentRule->getId()) {
            return [];
        }

        $installmentString = $installmentRule->getSettings();
        $installmentArray = json_decode($installmentString, true);
        if (is_array($installmentArray)) {
            return $installmentArray;
        }

        return [];
    }
}
