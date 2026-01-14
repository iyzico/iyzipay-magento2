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

namespace Iyzico\Iyzipay\Service;

use Exception;
use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Library\Model\CheckoutForm;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\RetrieveCheckoutFormRequest;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Model\Data\WebhookData;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class OrderService
{

    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected QuoteRepository $quoteRepository,
        protected Quote $quoteResource,
        protected UtilityHelper $utilityHelper,
        protected IyziErrorLogger $errorLogger,
        protected OrderJobService $orderJobService,
        protected ConfigHelper $configHelper
    ) {
    }

    /**
     * Create Order From Quote
     *
     * This function is responsible for creating an order from quote after successful payment.
     * This is used in quote-first payment flow where order is created only after payment success.
     *
     * @param  int  $quoteId
     * @param  CustomerSession|null  $customerSession
     * @param  CartManagementInterface  $cartManagement
     * @param  CheckoutForm  $response
     * @return int Order ID
     * @throws CouldNotSaveException|NoSuchEntityException|AlreadyExistsException|LocalizedException
     */
    public function createOrderFromQuote(
        int $quoteId,
        ?CustomerSession $customerSession,
        CartManagementInterface $cartManagement,
        CheckoutForm $response
    ): int {
        $quote = $this->quoteRepository->get($quoteId);

        // Set checkout method for guest customers
        $isLoggedIn = $customerSession && $customerSession->isLoggedIn();
        if (!$isLoggedIn) {
            $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
            if (!$quote->getCustomerEmail()) {
                if ($customerSession && $customerSession->getEmail()) {
                    $quote->setCustomerEmail($customerSession->getEmail());
                } elseif ($quote->getCustomerEmail()) {
                    // Use email from quote if available
                } elseif ($quote->getBillingAddress() && $quote->getBillingAddress()->getEmail()) {
                    $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
                }
            }
        }

        // Place order from quote
        $orderId = $cartManagement->placeOrder($quoteId);

        // Get the created order
        $order = $this->orderRepository->get($orderId);

        // Set payment method
        $payment = $order->getPayment();
        $payment->setMethod('iyzipay');

        // Set payment additional information
        $this->updatePaymentAdditionalInformation($payment, $response);

        // Determine order status based on payment status
        $paymentStatus = $response->getPaymentStatus();
        $status = $response->getStatus();
        $ordersByPaymentAndStatus = $this->utilityHelper->findOrderByPaymentAndStatus($paymentStatus, $status);

        // Set order state and status
        $order->setState($ordersByPaymentAndStatus['state']);
        $order->setStatus($ordersByPaymentAndStatus['status']);
        $order->addCommentToStatusHistory($ordersByPaymentAndStatus['comment']);

        // Add payment details to order history
        $paymentId = $response->getPaymentId() ?? "N/A";
        $conversationId = $response->getConversationId() ?? "N/A";
        $order->addCommentToStatusHistory("Payment ID: ".$paymentId." - Conversation ID: ".$conversationId);

        // Handle installment fee if applicable
        if ($response->getInstallment() > 1) {
            $order = $this->setOrderInstallmentFee($order, $response->getPaidPrice(), $response->getInstallment(), $response);
        }

        // Set email flag for successful payments
        if ($paymentStatus == 'SUCCESS' && $status == 'success') {
            $order->setCanSendNewEmailFlag(true);
        }

        // Handle errors if any
        if ($response->getErrorCode() != null || $response->getErrorMessage() != null) {
            $order->setCanSendNewEmailFlag(false);
            $errorMessage = "Error Code: ".$response->getErrorCode()." - Error Message: ".$response->getErrorMessage();
            if ($response->getErrorGroup() != null) {
                $errorMessage .= " - Error Group: ".$response->getErrorGroup();
            }
            $order->addCommentToStatusHistory($errorMessage);
        }

        // Save order
        $this->orderRepository->save($order);

        // Update order job status
        $this->orderJobService->setOrderJobStatus((string)$orderId, $ordersByPaymentAndStatus['orderJobStatus']);

        return $orderId;
    }

    /**
     * Update Order Payment Status
     *
     * This function is responsible for updating the order payment status based on the response.
     *
     * @param  string  $orderId
     * @param  mixed  $response
     * @param  string  $webhook
     * @return void
     */
    public function updateOrderPaymentStatus(string $orderId, mixed $response, string $webhook = 'no'): void
    {
        $ordersByPaymentAndStatus = [];
        $paymentStatus = '';
        $status = '';
        $error = [];

        $order = $this->findOrderById($orderId);
        $payment = $order->getPayment();

        if ($webhook != 'v3') {
            $paymentStatus = $response->getPaymentStatus();
            $status = $response->getStatus();
        } else {
            $paymentStatus = $response->getIyziEventType();
            $status = $response->getStatus();
        }

        if ($response->getErrorCode() != null || $response->getErrorMessage() != null) {
            $error['code'] = $response->getErrorCode();
            $error['message'] = $response->getErrorMessage();
            $error['error_group'] = $response->getErrorGroup();
        }

        $ordersByPaymentAndStatus = $this->utilityHelper->findOrderByPaymentAndStatus($paymentStatus, $status);

        $order->setState($ordersByPaymentAndStatus['state']);
        $order->setStatus($ordersByPaymentAndStatus['status']);
        $order->addCommentToStatusHistory($ordersByPaymentAndStatus['comment']);

        $this->orderJobService->setOrderJobStatus($orderId, $ordersByPaymentAndStatus['orderJobStatus']);

        if ($paymentStatus == 'SUCCESS' && $status == 'success') {
            $order->setCanSendNewEmailFlag(true);
        }

        if ($response->getInstallment() > 1) {
            $order = $this->setOrderInstallmentFee($order, $response->getPaidPrice(), $response->getInstallment(), $response);
        }

        if ($webhook === 'v3') {
            $this->updatePaymentAdditionalInformationForWebhook($payment, $response);
        }

        if ($webhook === 'yes') {
            $this->updatePaymentAdditionalInformation($payment, $response);
        }

        if ($webhook === 'no') {
            $paymentId = $response->getPaymentId() ?? "N/A";
            $conversationId = $response->getConversationId() ?? "N/A";
            $order->addCommentToStatusHistory("Payment ID: ".$paymentId." - Conversation ID:".$conversationId);
            $this->updatePaymentAdditionalInformation($payment, $response);
        }

        if (!empty($error)) {
            $order->setCanSendNewEmailFlag(false);
            $order->addCommentToStatusHistory("Error Code: ".$error['code']." - Error Message: ".$error['message']." - Error Group: ".$error['error_group']);
        }

        $this->orderRepository->save($order);
    }

    /**
     * Find Order By Id
     *
     * This function is responsible for finding the order by id.
     *
     * @param  string  $orderId
     * @return OrderInterface|null
     */
    public function findOrderById(string $orderId): OrderInterface|null
    {
        try {
            return $this->orderRepository->get($orderId);
        } catch (Exception $e) {
            $this->errorLogger->critical(
                "findOrderById: $orderId - Message: ".$e->getMessage(),
                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
            );
            return null;
        }
    }

    /**
     * Handle Installment Fee
     *
     * This function is responsible for handling the installment fee.
     * Uses maturityChargeAmount from response if available, otherwise calculates from paidPrice - grandTotal.
     *
     * @param $order
     * @param $paidPrice
     * @param $installment
     * @param $response
     * @return mixed
     */
    private function setOrderInstallmentFee($order, $paidPrice, $installment, $response = null): mixed
    {
        // Use maturityChargeAmount from response if available and greater than 0
        if ($response && method_exists($response, 'getMaturityChargeAmount')) {
            $maturityChargeAmount = $response->getMaturityChargeAmount();
            if ($maturityChargeAmount !== null && $maturityChargeAmount !== '') {
                // Convert to float to handle scientific notation like "0E-8"
                $maturityChargeAmountFloat = (float)$maturityChargeAmount;
                if ($maturityChargeAmountFloat > 0) {
                    $installmentPrice = $this->utilityHelper->parsePrice($maturityChargeAmountFloat);
                    $order->setInstallmentFee($installmentPrice);
                    $order->setInstallmentCount($installment);
                    return $order;
                }
            }
        }

        // Fallback to old calculation method (paidPrice - grandTotal)
        $grandTotal = $order->getGrandTotal();
        $installmentPrice = $this->utilityHelper->calculateInstallmentPrice($paidPrice, $grandTotal);

        $order->setInstallmentFee($installmentPrice);
        $order->setInstallmentCount($installment);

        return $order;
    }

    /**
     * Update Payment Additional Information
     *
     * This function is responsible for updating the payment additional information.
     *
     * @param  OrderPaymentInterface|null  $payment
     * @param  WebhookData  $webhookData
     * @return void
     */
    private function updatePaymentAdditionalInformationForWebhook(
        OrderPaymentInterface|null $payment,
        WebhookData $webhookData
    ): void {
        $payment->setLastTransId($webhookData->getIyziPaymentId());

        $paymentAdditionalInformation = $payment->getAdditionalInformation();
        $paymentAdditionalInformation['iyzico_webhook_event_type'] = $webhookData->getIyziEventType();
        $paymentAdditionalInformation['iyzico_webhook_status'] = $webhookData->getStatus();
        $paymentAdditionalInformation['iyzico_webhook_ref_code'] = $webhookData->getIyziReferenceCode();

        $payment->setAdditionalInformation($paymentAdditionalInformation);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, json_encode($paymentAdditionalInformation));
    }

    /**
     * Update Payment Additional Information
     *
     * This function is responsible for updating the payment additional information.
     *
     * @param  OrderPaymentInterface|null  $payment
     * @param  CheckoutForm  $response
     * @return void
     */
    private function updatePaymentAdditionalInformation(
        OrderPaymentInterface|null $payment,
        CheckoutForm $response
    ): void {
        $payment->setLastTransId($response->getPaymentId());

        $paymentAdditionalInformation = [
            'method_title' => 'iyzipay',
            'iyzico_payment_id' => $response->getPaymentId(),
            'iyzico_conversation_id' => $response->getConversationId(),
            'iyzico_md_status' => $response->getMdStatus()
        ];

        $payment->setAdditionalInformation($paymentAdditionalInformation);

        $payment->setTransactionId($response->getPaymentId());
        $payment->setIsTransactionClosed(0);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, json_encode($paymentAdditionalInformation));
    }

    /**
     * Retrieve and validate checkout form response
     *
     * @param  string  $token
     * @return CheckoutForm
     * @throws LocalizedException|LocalizedException
     */
    public function retrieveAndValidateCheckoutForm(string $token, string $conversationId): CheckoutForm
    {
        $locale = $this->configHelper->getLocale();
        $apiKey = $this->configHelper->getApiKey();
        $secretKey = $this->configHelper->getSecretKey();
        $baseUrl = $this->configHelper->getBaseUrl();

        $request = new RetrieveCheckoutFormRequest();
        $request->setLocale($locale);
        $request->setConversationId($conversationId);
        $request->setToken($token);

        $options = new Options();
        $options->setBaseUrl($baseUrl);
        $options->setApiKey($apiKey);
        $options->setSecretKey($secretKey);

        $response = CheckoutForm::retrieve($request, $options);

        return $response;
    }
}
