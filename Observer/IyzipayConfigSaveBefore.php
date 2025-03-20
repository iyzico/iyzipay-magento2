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
use Iyzipay\Options;
use Iyzipay\Request\RetrieveProtectedOverleyScriptRequest;
use Iyzipay\Model\ProtectedOverleyScript;
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

    if (!empty($postData['groups']['iyzipay']['fields']['active'])) {


      $apiKey = $postData['groups']['iyzipay']['fields']['api_key']['value'];
      $secretKey = $postData['groups']['iyzipay']['fields']['secret_key']['value'];
      $randNumer = rand(100000, 99999999);
      $sandboxStatus = $this->_scopeConfig->getValue('payment/iyzipay/sandbox');
      $baseUrl = $sandboxStatus ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';

      $storeId = $this->_storeManager->getStore()->getId();
      $locale = $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

      $request = new RetrieveProtectedOverleyScriptRequest();
      $request->setLocale($this->_iyzicoHelper->cutLocale($locale));
      $request->setConversationId($randNumer);
      $request->setPosition($postData['groups']['iyzipay']['fields']['overlayscript']['value']);

      $options = new Options();
      $options->setApiKey($apiKey);
      $options->setSecretKey($secretKey);
      $options->setBaseUrl($baseUrl);

      $response = ProtectedOverleyScript::retrieve($request, $options);

      if ($response->getStatus() == 'success') {
        $this->_configWriter->save('payment/iyzipay/protectedShopId',  $response->getProtectedShopId(), $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
      }
    }
  }


  public function webhookSetControll($postData)
  {
    $webhookActive = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key_active');
    if (!$webhookActive) {
      $this->_configWriter->save('payment/iyzipay/webhook_url_key_active',  '0', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }
  }


  public function webhookUrlKey($postData)
  {

    $webhookUrlKey = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key');
    if (!$webhookUrlKey) {
      $webhookUrlKeyUniq = substr(base64_encode(time() . mt_rand()), 15, 6);
      $this->_configWriter->save('payment/iyzipay/webhook_url_key',  $webhookUrlKeyUniq, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }
  }
}
