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

use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Helper\ObjectHelper;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Library\Model\CheckoutFormInitialize;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\CreateCheckoutFormInitializeRequest;
use Iyzico\Iyzipay\Model\IyziCardFactory;
use Iyzico\Iyzipay\Service\OrderJobService;
use Iyzico\Iyzipay\Service\OrderService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;


class IyzipayRequest implements ActionInterface
{
    public function __construct
    (
        protected readonly CheckoutSession $checkoutSession,
        protected readonly CustomerSession $customerSession,
        protected readonly IyziCardFactory $iyziCardFactory,
        protected readonly JsonFactory $resultJsonFactory,
        protected readonly Quote $quote,
        protected readonly ConfigHelper $configHelper,
        protected readonly UtilityHelper $utilityHelper,
        protected readonly ObjectHelper $objectHelper,
        protected readonly OrderJobService $orderJobService,
        protected readonly OrderService $orderService,
        protected readonly CartManagementInterface $cartManagement,
        protected readonly CartRepositoryInterface $cartRepository
    ) {
    }

    /**
     * Execute
     *
     * This function is responsible for executing the payment request.
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(): Json
    {
        try {
            // Get the configuration values
            $apiKey = $this->configHelper->getApiKey();
            $secretKey = $this->configHelper->getSecretKey();
            $baseUrl = $this->configHelper->getBaseUrl();
            $callbackUrl = $this->configHelper->getCallbackUrl();
            $paymentSource = $this->configHelper->getPaymentSource();
            $locale = $this->configHelper->getLocale();
            $currency = $this->configHelper->getCurrency();

            // Ensure the cookies are same site
            $this->utilityHelper->ensureCookiesSameSite();
            $resultJson = $this->resultJsonFactory->create();
            $checkoutSession = $this->checkoutSession->getQuote();

            // Configure the buyer
            $customerId = $this->utilityHelper->getCustomerId($this->customerSession);
            $cardUserKey = $this->utilityHelper->getCustomerCardUserKey($this->iyziCardFactory, $customerId, $apiKey);
            $buyer = $this->objectHelper->createBuyer($checkoutSession);

            // Configure the basket
            $basketId = $this->checkoutSession->getQuoteId();
            $conversationId = $this->utilityHelper->generateConversationId($basketId);
            $basketItems = $this->objectHelper->createBasketItems($checkoutSession);

            // Configure the price
            $price = $this->utilityHelper->calculateSubtotalPrice($checkoutSession);
            $paidPrice = $this->utilityHelper->parsePrice(round($checkoutSession->getGrandTotal(), 2));

            // Configure the address
            $shippingAddress = $this->objectHelper->createShippingAddress($checkoutSession);
            $billingAddress = $this->objectHelper->createBillingAddress($checkoutSession);

            // Create the request
            $request = new CreateCheckoutFormInitializeRequest();
            $request->setLocale($locale);
            $request->setConversationId($conversationId);
            $request->setPrice($price);
            $request->setPaidPrice($paidPrice);
            $request->setCurrency($currency);
            $request->setBasketId($basketId);
            $request->setPaymentGroup("PRODUCT");
            $request->setCallbackUrl($callbackUrl);
            $request->setPaymentSource($paymentSource);
            $request->setBuyer($buyer);
            $request->setShippingAddress($shippingAddress);
            $request->setBillingAddress($billingAddress);
            $request->setBasketItems($basketItems);
            $request->setCardUserKey($cardUserKey);
            $request->setGoBackUrl($this->configHelper->getGoBackUrl($basketId));

            // Create the options
            $options = new Options();
            $options->setBaseUrl($baseUrl);
            $options->setApiKey($apiKey);
            $options->setSecretKey($secretKey);

            $response = CheckoutFormInitialize::create($request, $options);

            $responseConversationId = $response->getConversationId();
            $responseToken = $response->getToken();
            $responseSignature = $response->getSignature();

            $calculateSignature = $this->utilityHelper->calculateHmacSHA256Signature([
                $responseConversationId,
                $responseToken
            ], $secretKey);

            if ($responseSignature === $calculateSignature) {
                $this->utilityHelper->storeSessionData($checkoutSession, $this->customerSession);

                $oldOrderId = $this->orderJobService->findOrderIdByQuoteId($basketId);

                if ($oldOrderId) {
                    $this->orderService->cancelOrder($oldOrderId);
                }

                $orderId = $this->orderService->placeOrder($basketId, $this->customerSession, $this->cartManagement);
                $this->orderJobService->saveIyziOrderJobTable($response, $basketId, $orderId);
                return $resultJson->setData([
                    'success' => true,
                    'url' => $response->getPaymentPageUrl()
                ]);
            }

            return $resultJson->setData([
                'success' => false,
                'message' => "Signature Mismatch",
                'code' => "0"
            ]);
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}
