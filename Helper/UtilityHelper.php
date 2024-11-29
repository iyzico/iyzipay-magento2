<?php

namespace Iyzico\Iyzipay\Helper;

use Iyzico\Iyzipay\Model\IyziCardFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;

class UtilityHelper
{
    private const COOKIE_EXPIRE_TIME = 86400;

    /**
     * Calculate Installment Price
     *
     * @param  float  $paidPrice
     * @param  float  $grandTotal
     * @return string
     */
    public function calculateInstallmentPrice(float $paidPrice, float $grandTotal): string
    {
        return $this->parsePrice($paidPrice - $grandTotal);
    }

    /**
     * Trailing Zero
     *
     * @param  float  $price
     * @return string
     */
    public function parsePrice(float $price): string
    {
        if (strpos($price, ".") === false) {
            return $price . ".0";
        }

        $subStrIndex = 0;
        $priceReversed = strrev($price);
        for ($i = 0; $i < strlen($priceReversed); $i++) {
            if (strcmp($priceReversed[$i], "0") == 0) {
                $subStrIndex = $i + 1;
            } else {
                if (strcmp($priceReversed[$i], ".") == 0) {
                    $priceReversed = "0" . $priceReversed;
                    break;
                } else {
                    break;
                }
            }
        }

        return strrev(substr($priceReversed, $subStrIndex));
    }

    /**
     * Calculate Subtotal Price
     *
     * @param  $order
     * @return string
     */
    public function calculateSubTotalPrice($order): string
    {
        $price = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $price += round($item->getPrice(), 2);
        }

        $price += $order->getShippingAddress()->getShippingAmount() ?? 0;
        return $this->parsePrice($price);
    }

    /**
     * Ensure Cookies Same Site
     *
     * Sets SameSite=None; Secure on specified cookies.
     *
     * @return void
     */
    public function ensureCookiesSameSite(): void
    {
        $cookieNamesToCheck = [
            'PHPSESSID',
            'adminhtml',
            'frontend',
            'mage-cache-sessid',
            'mage-cache-storage',
            'mage-cache-storage-section-invalidation',
            'mage-messages',
            'mage-translation-file-version',
            'mage-translation-storage'
        ];

        $cookieNamesToCheck = array_flip($cookieNamesToCheck);

        foreach ($_COOKIE as $cookieName => $value) {
            if (isset($cookieNamesToCheck[$cookieName])) {
                $this->applyCookieSettings(
                    $cookieName,
                    $value,
                    time() + self::COOKIE_EXPIRE_TIME,
                    $_SERVER['SERVER_NAME']
                );
            }
        }
    }

    /**
     * Apply Cookie Settings
     *
     * @param  string  $name  Name of the cookie
     * @param  string  $value  Value of the cookie
     * @param  int  $expire  Expiration time
     * @param  string  $domain  Domain for the cookie
     * @return void
     */
    private function applyCookieSettings(string $name, string $value, int $expire, string $domain): void
    {
        if (PHP_VERSION_ID < 70300) {
            setcookie($name, $value, $expire, "/; samesite=None", $domain, true, true);
        } else {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => "/",
                'domain' => $domain,
                'samesite' => 'None',
                'secure' => true,
                'httponly' => true
            ]);
        }
    }

    /**
     * Concatenate Strings
     *
     * @param  string  ...$address
     * @return string
     */
    public function concatenateStrings(string ...$address): string
    {
        $address = array_map('trim', $address);
        return implode(' ', $address);
    }

    /**
     * Validate String
     *
     * @param  mixed  $string
     * @return string
     */
    public function validateString(mixed $string): string
    {
        if (is_array($string)) {
            return implode(' ', $string);
        }

        if (!empty(trim($string))) {
            return $string;
        }

        return "NOT PROVIDED";
    }

    /**
     * Generate Conversation Id
     *
     * @param  int  $quoteId
     * @return string
     */
    public function generateConversationId(int $quoteId): string
    {
        return 'QI' . $quoteId . 'T' . time();
    }

    /**
     * Get Customer Id
     *
     * @param  CustomerSession $customerSession
     * @return int|null
     */
    public function getCustomerId(CustomerSession $customerSession): ?int
    {
        return $customerSession->isLoggedIn() ? $customerSession->getCustomerId() : 0;
    }

    /**
     * Get Customer Card User Key
     *
     * This function is responsible for getting the customer card user key.
     *
     * @param  IyziCardFactory  $iyziCardFactory
     * @param  int  $customerId
     * @param  string  $apiKey
     * @return string
     */
    public function getCustomerCardUserKey(IyziCardFactory $iyziCardFactory, int $customerId, string $apiKey): string
    {
        if ($customerId) {
            $iyziCardFind = $iyziCardFactory->create()->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('api_key', $apiKey)
                ->addFieldToSelect('card_user_key');
            $iyziCardFind = $iyziCardFind->getData();
            return !empty($iyziCardFind[0]['card_user_key']) ? $iyziCardFind[0]['card_user_key'] : '';
        }
        return '';
    }


    /**
     * Calculate HMAC SHA256 Signature
     *
     * @param  array  $params
     * @param  string  $secretKey
     * @return string
     */
    public function calculateHmacSHA256Signature(array $params, string $secretKey): string
    {
        $dataToSign = implode(':', $params);
        $mac = hash_hmac('sha256', $dataToSign, $secretKey, true);

        return bin2hex($mac);
    }

    /**
     * Get Locale Name
     *
     * @param $locale
     * @return string
     */
    public function cutLocale($locale): string
    {

        $locale = explode('_', $locale);
        return $locale[0];
    }

    /**
     * Store Session Data
     *
     * This function is responsible for storing the session data.
     *
     * @param  Quote  $checkoutSession
     * @param  CustomerSession  $customerSession
     * @return void
     */
    public function storeSessionData(
        Quote $checkoutSession,
        CustomerSession $customerSession
    ): void {
        $customerEmail = $checkoutSession->getBillingAddress()->getEmail();
        $quoteId = $checkoutSession->getId();
        $checkoutSession->setGuestQuoteId($quoteId);
        $customerSession->setEmail($customerEmail);
    }

}
