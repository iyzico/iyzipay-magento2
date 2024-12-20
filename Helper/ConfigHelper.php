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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

readonly class ConfigHelper
{
    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected ScopeConfigInterface $scopeConfig,
        protected WriterInterface $configWriter
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
     * Get Locale Language Code
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getLocale(): mixed
    {
        $fullLocale = $this->scopeConfig->getValue(
            'general/locale/code',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );

        return explode('_', $fullLocale)[0] ?? $fullLocale;
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
        return $this->storeManager->getStore()->getBaseUrl()."iyzico/response/iyzipayresponse";
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
    public function getGoBackUrl(string $token): string
    {
        return $this->storeManager->getStore()->getBaseUrl()."iyzico/redirect/backtostore?token=".$token;
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
     * Get Iyzipay Magento Payment Source
     *
     * This function is responsible for getting the payment source.
     */
    public function getPaymentSource(): string
    {
        return "MAGENTO2|".$this->getMagentoVersion()."|SPACE-2.1.4";
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

    /**
     * Get Iyzipay OverlayScript
     *
     * This function is responsible for getting the overlay script.
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getOverlayScript(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/overlayscript',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Base URL for the given Website ID
     *
     * @param  int|null  $websiteId
     * @return string
     * @throws LocalizedException
     */
    public function getWebsiteBaseUrl(?int $websiteId): string
    {
        if ($websiteId) {
            $website = $this->storeManager->getWebsite($websiteId);
            return $website->getDefaultStore()->getBaseUrl();
        }
        return $this->storeManager->getDefaultStoreView()->getBaseUrl();
    }

    /**
     * Get Common Cron Settings
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getCommonCronSettings(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/common_cron_settings',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Get Custom Cron Settings
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getCustomCronSettings(): mixed
    {
        return $this->scopeConfig->getValue(
            'payment/iyzipay/custom_cron_settings',
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }

    /**
     * Set Cron Settings
     *
     * @param $value
     * @return void
     * @throws LocalizedException
     */
    public function setCronSettings($value): void
    {
        $this->configWriter->save(
            'crontab/default/jobs/iyzico_process_pending_orders/schedule/cron_expr',
            $value,
            $this->getScopeInterface(),
            $this->getWebsiteId()
        );
    }
}
