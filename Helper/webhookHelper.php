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

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;


class webhookHelper
{

	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->config               = $context->getScopeConfig();
        $this->checkoutSession      = $checkoutSession;
        $this->orderFactory         = $orderFactory;
        $this->_storeManager        = $storeManager;
    }

	/**
     * @return string
     */
    public function getScopeInterface(): string
    {
        return ScopeInterface::SCOPE_STORE;
    }

    /**
     * @return mixed
     */
    public function getWebhookUrl()
    {
        return $this->config->getValue('payment/iyzipay/webhook_url_key', $this->getScopeInterface());
    }
    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->config->getValue('payment/iyzipay/secret_key', $this->getScopeInterface());
    }

    /**
     * @return mixed
     */
    public function getOrderStatus()
    {
        return $this->config->getValue('payment/iyzipay/order_status', $this->getScopeInterface());
    }

    /**
     * @param  $message
     * @param  $status
     * @return mixed
     */
    public function webhookHttpResponse($message , $status){
      $httpMessage = array('message' => $message , 'status' => $status);
      header('Content-Type: application/json, Status: '. $status, true, $status);
      echo json_encode($httpMessage);
      exit();

    }














}
