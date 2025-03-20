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

namespace Iyzico\Iyzipay\Controller\Request;

use Iyzico\Iyzipay\Controller\IyzicoBase\IyzicoFormObjectGenerator;
use Iyzico\Iyzipay\Helper\IyzicoHelper;
use Iyzico\Iyzipay\Model\IyziCardFactory;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;


class IyzicoCheckoutForm extends Action
{

    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_checkoutSession;
    protected $_customerSession;
    protected $_scopeConfig;
    protected $_iyziCardFactory;
    protected $_storeManager;
    protected $_iyzicoHelper;

    public function __construct(
        Context               $context,
        EncoderInterface      $encoder,
        PageFactory           $pageFactory,
        CheckoutSession       $checkoutSession,
        CustomerSession       $customerSession,
        ScopeConfigInterface  $scopeConfig,
        IyziCardFactory       $iyziCardFactory,
        StoreManagerInterface $storeManager
    )
    {
        $this->_context = $context;
        $this->_pageFactory = $pageFactory;
        $this->_jsonEncoder = $encoder;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
        $this->_iyziCardFactory = $iyziCardFactory;
        $this->_storeManager = $storeManager;
        $this->_iyzicoHelper = new IyzicoHelper();
    }

    public function execute()
    {
        $iyzicoFormObject = new IyzicoFormObjectGenerator();
        $objectManager = ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $magentoVersion = $productMetadata->getVersion();
        $postData = $this->getRequest()->getPostValue();
        $checkoutSession = $this->_checkoutSession->getQuote();

        $apiKey = $this->_scopeConfig->getValue('payment/iyzipay/api_key');
        $secretKey = $this->_scopeConfig->getValue('payment/iyzipay/secret_key');
        $sandboxStatus = $this->_scopeConfig->getValue('payment/iyzipay/sandbox');
        $baseUrl = $sandboxStatus ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';

        $storeId = $this->_storeManager->getStore()->getId();
        $locale = $this->_iyzicoHelper->cutLocale($this->_scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId));
        $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $callBack = $this->_storeManager->getStore()->getBaseUrl();
        $cardId = $checkoutSession->getId();
        $customerId = 0;
        $guestEmail = false;
        $customerCardUserKey = '';
        $price = $this->_iyzicoHelper->subTotalPriceCalc($checkoutSession);
        $paidPrice = $this->_iyzicoHelper->priceParser(round($checkoutSession->getGrandTotal(), 2));
        $callBackUrl = $callBack . "Iyzico_Iyzipay/response/iyzicocheckoutform";
        $paymentSource = "MAGENTO2|" . $magentoVersion . "|SPACE-2.1.4";

        if (isset($postData['iyziQuoteEmail']) && isset($postData['iyziQuoteId'])) {
            $this->_customerSession->setEmail($postData['iyziQuoteEmail']);
            $this->_checkoutSession->setGuestQuoteId($postData['iyziQuoteId']);
            $guestEmail = $postData['iyziQuoteEmail'];
        }

        $buyer = $iyzicoFormObject->generateBuyer($checkoutSession, $guestEmail);
        $billingAddress = $iyzicoFormObject->generateBillingAddress($checkoutSession);
        $shippingAddress = $iyzicoFormObject->generateShippingAddress($checkoutSession);
        $basketItems = $iyzicoFormObject->generateBasketItems($checkoutSession);

        $this->checkAndSetCookieSameSite();

        if ($this->_customerSession->isLoggedIn()) {
            $customerId = $this->_customerSession->getCustomerId();
        }

        if ($customerId) {
            $iyziCardFind = $this->_iyziCardFactory->create()->getCollection()->addFieldToFilter('customer_id', $customerId)->addFieldToFilter('api_key', $apiKey)->addFieldToSelect('card_user_key');
            $iyziCardFind = $iyziCardFind->getData();
            $customerCardUserKey = !empty($iyziCardFind[0]['card_user_key']) ? $iyziCardFind[0]['card_user_key'] : '';
        }

        $options = new Options();
        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);
        $options->setBaseUrl($baseUrl);

        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale($locale);
        $request->setConversationId($cardId);
        $request->setPrice($price);
        $request->setPaidPrice($paidPrice);
        $request->setCurrency($currency);
        $request->setPaymentGroup("PRODUCT");
        $request->setForceThreeDS("0");
        $request->setCallbackUrl($callBackUrl);
        $request->setCardUserKey($customerCardUserKey);
        $request->setPaymentSource($paymentSource);
        $request->setBuyer($buyer);
        $request->setBillingAddress($billingAddress);
        $request->setShippingAddress($shippingAddress);
        $request->setBasketItems($basketItems);

        $response = CheckoutFormInitialize::create($request, $options);

        if ($response->getStatus() == 'success') {
            $this->_customerSession->setIyziToken($response->getToken());
            $result = $response->getPaymentPageUrl();
        } else {
            $result = $response->getErrorMessage();
        }

        $this->getResponse()->representJson($result);
    }

    private function checkAndSetCookieSameSite()
    {
        $checkCookieNames = array('PHPSESSID', 'OCSESSID', 'default', 'PrestaShop-', 'wp_woocommerce_session_');
        foreach ($_COOKIE as $cookieName => $value) {
            foreach ($checkCookieNames as $checkCookieName) {
                if (stripos($cookieName, $checkCookieName) === 0) {
                    $this->setcookieSameSite($cookieName, $_COOKIE[$cookieName], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);
                }
            }
        }
    }

    private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly)
    {
        if (PHP_VERSION_ID < 70300) {
            setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
        } else {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'samesite' => 'None',
                'secure' => $secure,
                'httponly' => $httponly
            ]);


        }
    }

}
