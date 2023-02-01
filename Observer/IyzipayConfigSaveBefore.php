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

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Iyzico\Iyzipay\Controller\IyzicoBase\IyzicoPkiStringBuilder;
use Iyzico\Iyzipay\Controller\IyzicoBase\IyzicoRequest;
use stdClass;

class IyzipayConfigSaveBefore implements ObserverInterface
{

    protected $_scopeConfig;
    protected $_storeManager;
    protected $_iyzicoHelper;
    protected $_configWriter;
    protected $_request;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Iyzico\Iyzipay\Helper\IyzicoHelper $iyzicoHelper,
       \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
       \Magento\Framework\App\Request\Http $request
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_iyzicoHelper = $iyzicoHelper;
        $this->_configWriter = $configWriter;
        $this->_request = $request;
    }

    public function execute(EventObserver $observer)
    {


        $postData = $this->_request->getPostValue();
        $this->webhookUrlKey($postData);
        $this->webhookSetControll($postData);
        $this->initSetWebhookUrlKey($postData);

        if(!empty($postData['groups']['iyzipay']['fields']['active'])) {


            $apiKey = $postData['groups']['iyzipay']['fields']['api_key']['value'];
            $secretKey = $postData['groups']['iyzipay']['fields']['secret_key']['value'];
            $randNumer = rand(100000,99999999);

            $storeId = $this->_storeManager->getStore()->getId();
            $locale = $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

            $overlayObject = new stdClass();
            $overlayObject->locale = $this->_iyzicoHelper->cutLocale($locale);
            $overlayObject->conversationId = $randNumer;
            $overlayObject->position = $postData['groups']['iyzipay']['fields']['overlayscript']['value'];

            $iyzicoPkiStringBuilder = new IyzicoPkiStringBuilder();
            $iyzicoRequest = new IyzicoRequest();

            $pkiString = $iyzicoPkiStringBuilder->pkiStringGenerate($overlayObject);
            $authorization = $iyzicoPkiStringBuilder->authorizationGenerate($pkiString,$apiKey,$secretKey,$randNumer);

            $iyzicoJson = json_encode($overlayObject,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

            $requestResponse = $iyzicoRequest->iyzicoOverlayScriptRequest($iyzicoJson,$authorization);

            if($requestResponse->status == 'success') {

                $this->_configWriter->save('payment/iyzipay/protectedShopId',  $requestResponse->protectedShopId, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);

            }

        }

    }

    public function initSetWebhookUrlKey($postData)
    {

      $webhookActive = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key_active');
      if($webhookActive == 0)
      {
        $apiKey = $postData['groups']['iyzipay']['fields']['api_key']['value'];
        $secretKey = $postData['groups']['iyzipay']['fields']['secret_key']['value'];
        if(isset($apiKey) && isset($secretKey))
        {
          $randNumer = rand(100000,99999999);
          $sandboxStatus = $this->_scopeConfig->getValue('payment/iyzipay/sandbox');
          $baseUrl = 'https://api.iyzipay.com';

          if($sandboxStatus)
              $baseUrl = 'https://sandbox-api.iyzipay.com';


          $webhook_url_key = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key');

          $setWebhookUrl = new stdClass();
          $setWebhookUrl->webhookUrl = $this->_storeManager->getStore()->getBaseUrl().'rest/V1/iyzico/webhook/'.$webhook_url_key;

          $iyzicoPkiStringBuilder = new IyzicoPkiStringBuilder();
          $iyzicoRequest = new IyzicoRequest();

          $pkiString = $iyzicoPkiStringBuilder->pkiStringGenerate($setWebhookUrl);
          $authorization = $iyzicoPkiStringBuilder->authorizationGenerate($pkiString,$apiKey,$secretKey,$randNumer);

          $iyzicoJson = json_encode($setWebhookUrl,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

          $requestResponseWebhook = $iyzicoRequest->iyzicoPostWebhookUrlKey($baseUrl,$iyzicoJson,$authorization);
          if($requestResponseWebhook->merchantNotificationUpdateStatus == 'UPDATED' || $requestResponseWebhook->merchantNotificationUpdateStatus == 'CREATED')
          {
            $this->_configWriter->save('payment/iyzipay/webhook_url_key_active',  '1', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);

          }
          else {
            return $this->_configWriter->save('payment/iyzipay/webhook_url_key_active',  '2', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);

          }
      }

      }

    }


    public function webhookSetControll($postData)
    {

      $webhookActive = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key_active');
      if(!$webhookActive)
      {
        $this->_configWriter->save('payment/iyzipay/webhook_url_key_active',  '0', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
      }
    }


    public function webhookUrlKey($postData)
    {

      $webhookUrlKey = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key');
      if(!$webhookUrlKey)
      {
        $webhookUrlKeyUniq = substr(base64_encode(time() . mt_rand()),15,6);
        $this->_configWriter->save('payment/iyzipay/webhook_url_key',  $webhookUrlKeyUniq , $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);

      }
    }


}
