<?php

namespace Iyzico\Iyzipay\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigHelper
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get Secret Key
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getSecretKey(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/secret_key',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Scope Type
     *
     * @return string
     */
    public function getScopeInterface(): string
    {
        return ScopeInterface::SCOPE_WEBSITES;
    }

    /**
     * Magento Website Id
     *
     * @return int
     * @throws LocalizedException
     */
    public function getWebsiteId(): int
    {
        return $this->storeManager->getWebsite()->getId();
    }

    /**
     * Get Api Key
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getApiKey(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/api_key',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Webhook Url Key Active
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getWebhookUrlKeyActive(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/webhook_url_key_active',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }


    /**
     * Get Sandbox Status
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getSandboxStatus(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/sandbox',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Api Key
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getLocale(): mixed
    {
        return $this->scopeConfig->getValue(
            'general/locale/code',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Currency
     *
     * This function is responsible for getting the currency.
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Get Callback Url
     *
     * This function is responsible for getting the callback url.
     *
     * @throws NoSuchEntityException
     */
    public function getCallbackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . "iyzico/response/iyzipayresponse";
    }

    /**
     * Get Base URL
     *
     * @return string
     * @throws LocalizedException
     */
    public function getBaseUrl(): string
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/sandbox',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        ) ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';
    }

    /**
     * Get GoBack Url
     *
     * This function is responsible for getting the go back url.
     *
     * @throws NoSuchEntityException
     */
    public function getGoBackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . "checkout/cart";
    }

    /**
     * Get Webhook Url Key
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getWebhookUrlKey(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/webhook_url_key',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Iyzipay Module order_status from configuration : TODO
     *
     * This function is responsible for getting the order status from the configuration.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getIyzipayOrderStatus(): string
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/order_status',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Iyzipay Magento Payment Source
     *
     * This function is responsible for getting the payment source.
     */
    public function getPaymentSource(): string
    {
        return "MAGENTO2|" . $this->getMagentoVersion() . "|SPACE-2.1.4";
    }

    /**
     * Get Magento Version
     *
     * This function is responsible for getting the magento version.
     */
    public function getMagentoVersion()
    {
        $objectManager = ObjectManager::getInstance();
        $productMetaData = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        return $productMetaData->getVersion();
    }

}
