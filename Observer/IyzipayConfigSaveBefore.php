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

namespace Iyzico\Iyzipay\Observer;

use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Library\Model\ProtectedOverleyScript;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\RetrieveProtectedOverleyScriptRequest;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;


class IyzipayConfigSaveBefore implements ObserverInterface
{
    public function __construct(
        protected UtilityHelper $utilityHelper,
        protected ConfigHelper $configHelper,
        protected WriterInterface $configWriter,
        protected Http $request
    ) {
    }

    /**
     * Execute observer
     *
     * This method is called when the event specified in the events.xml file is triggered.
     *
     * @param  Observer  $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $this->webhookUrlKey();

        $postData = $this->request->getPostValue();

        if (!empty($postData['groups']['iyzipay']['fields']['active'])) {
            $baseUrl = $this->configHelper->getBaseUrl();
            $apiKey = $postData['groups']['iyzipay']['fields']['api_key']['value'] ?? $this->configHelper->getApiKey();
            $secretKey = $postData['groups']['iyzipay']['fields']['secret_key']['value'] ?? $this->configHelper->getSecretKey();

            $websiteId = $this->configHelper->getWebsiteId();
            $locale = $this->configHelper->getLocale();
            $cutLocale = $this->utilityHelper->cutLocale($locale);
            $position = $postData['groups']['iyzipay']['fields']['overlayscript']['value'] ?? $this->configHelper->getOverlayScript();

            if ($position != null) {
                $request = new RetrieveProtectedOverleyScriptRequest();
                $request->setLocale($cutLocale);
                $request->setConversationId(rand(100000, 99999999));
                $request->setPosition($position);

                $options = new Options();
                $options->setApiKey($apiKey);
                $options->setSecretKey($secretKey);
                $options->setBaseUrl($baseUrl);

                $response = ProtectedOverleyScript::retrieve($request, $options);

                if ($response->getStatus() == 'success') {
                    $this->configWriter->save(
                        'payment/iyzipay/protectedShopId',
                        $response->getProtectedShopId(),
                        ScopeInterface::SCOPE_WEBSITES,
                        $websiteId
                    );
                }
            }
        }
    }

    /**
     * Get or Create Webhook Url Key
     *
     * This method webhook url key control and create
     *
     * @throws LocalizedException
     */
    private function webhookUrlKey(): void
    {
        $websiteId = $this->configHelper->getWebsiteId();
        $webhookUrlKey = $this->configHelper->getWebhookUrlKey();

        if (!$webhookUrlKey) {
            $webhookUrlKeyUniq = substr(base64_encode(time().mt_rand()), 15, 6);
            $this->configWriter->save(
                'payment/iyzipay/webhook_url_key',
                $webhookUrlKeyUniq,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }
    }
}
