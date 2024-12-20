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
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

readonly class OrderService
{

    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected QuoteRepository $quoteRepository,
        protected Quote $quoteResource,
        protected UtilityHelper $utilityHelper,
        protected IyziErrorLogger $errorLogger,
        protected OrderJobService $orderJobService,
        protected ReservationBuilderInterface $reservationBuilder,
        protected AppendReservationsInterface $appendReservations,
        protected ConfigHelper $configHelper
    ) {
    }

    /**
     * Place Order
     *
     * This function is responsible for placing the order and setting the status to pending_payment.
     *
     * @throws CouldNotSaveException|NoSuchEntityException|AlreadyExistsException
     */
    public function placeOrder(
        int $quoteId,
        CustomerSession $customerSession,
        CartManagementInterface $cartManagement
    ): int {
        $quote = $this->quoteRepository->get($quoteId);
        if ($customerSession->isLoggedIn()) {
            $orderId = $cartManagement->placeOrder($quoteId);
        } else {
            $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
            $quote->setCustomerEmail($customerSession->getEmail());
            $orderId = $cartManagement->placeOrder($quoteId);
        }

        $quote->setIsActive(1);
        $this->quoteResource->save($quote);

        $order = $this->orderRepository->get($orderId);
        $comment = __("START_ORDER");

        $order->setState('pending_payment')->setStatus('pending_payment');
        $order->addCommentToStatusHistory($comment);
        $order->getPayment()->setMethod('iyzipay');

        $this->orderRepository->save($order);

        return $orderId;
    }

    /**
     * Update Order Payment Status
     *
     * This function is responsible for updating the order payment status based on the response.
     *
     * @param  string  $orderId
     * @param  mixed  $response
     * @param  bool  $isWebhook
     * @return void
     * @throws Exception
     */
    public function updateOrderPaymentStatus(string $orderId, mixed $response, string $webhook = 'no'): void
    {
        $ordersByPaymentAndStatus = [];
        $paymentStatus = '';
        $status = '';

        $order = $this->findOrderById($orderId);
        $payment = $order->getPayment();

        if($webhook != 'v3'){
            $paymentStatus = $response->getPaymentStatus();
            $status = $response->getStatus();
        }else{
            $paymentStatus = $response->getIyziEventType();
            $status = $response->getStatus();
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
            $order = $this->setOrderInstallmentFee($order, $response->getPaidPrice(), $response->getInstallment());
        }

        if ($webhook === 'v3') {
            $this->updatePaymentAdditionalInformationForWebhook($payment, $response);
        }

        if ($webhook === 'yes') {
            $this->updatePaymentAdditionalInformation($payment, $response);
        }

        if ($webhook === 'no') {
            $order->addCommentToStatusHistory("Payment ID: " . $response->getPaymentId() . " - Conversation ID:" . $response->getConversationId());
            $this->updatePaymentAdditionalInformation($payment, $response);
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
                "findOrderById: $orderId - Message: " . $e->getMessage(),
                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
            );
            return null;
        }
    }

    /**
     * Handle Installment Fee
     *
     * This function is responsible for handling the installment fee.
     *
     * @param $order
     * @param $paidPrice
     * @param $installment
     * @return mixed
     */
    private function setOrderInstallmentFee($order, $paidPrice, $installment): mixed
    {
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
        ];

        $payment->setAdditionalInformation($paymentAdditionalInformation);

        $payment->setTransactionId($response->getPaymentId());
        $payment->setIsTransactionClosed(0);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, json_encode($paymentAdditionalInformation));
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

        $paymentAdditionalInformation = [
            'method_title' => 'iyzipay',
            'iyzico_payment_id' => $webhookData->getIyziPaymentId(),
            'iyzico_conversation_id' => $webhookData->getPaymentConversationId(),
            'iyzico_webhook_event_type' => $webhookData->getIyziEventType(),
            'iyzico_webhook_status' => $webhookData->getStatus(),
            'iyzico_webhook_ref_Code' => $webhookData->getIyziReferenceCode(),
        ];

        $payment->setAdditionalInformation($paymentAdditionalInformation);

        $payment->setTransactionId($webhookData->getIyziPaymentId());
        $payment->setIsTransactionClosed(0);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, json_encode($paymentAdditionalInformation));
    }

    /**
     * Cancel Order
     * This function is responsible for canceling the order.
     *
     * @param  string  $orderId
     * @return void
     */
    public function cancelOrder(string $orderId): void
    {
        $order = $this->findOrderById($orderId);
        $order->setState("canceled")->setStatus("canceled");
        $order->addCommentToStatusHistory(__("Order has been canceled."));
        $this->orderRepository->save($order);
    }

    /**
     * Relase Stock
     *
     * This function is responsible for releasing the stock.
     *
     * @param $magentoOrder
     * @return void
     */
    public function releaseStock($magentoOrder): void
    {
        try {
            $reservations = [];
            $processedSkus = [];

            foreach ($magentoOrder->getAllItems() as $item) {
                $sku = $item->getSku();
                $quantity = $item->getQtyOrdered();
                $stockId = 1;
                $metadata = "Released stock for Order ID: {$magentoOrder->getEntityId()}";

                if (in_array($sku, $processedSkus, true)) {
                    continue;
                }

                $reservation = $this->reservationBuilder
                    ->setSku($sku)
                    ->setQuantity($quantity)
                    ->setStockId($stockId)
                    ->setMetadata($metadata)
                    ->build();

                $reservations[] = $reservation;
                $processedSkus[] = $sku;
            }

            if (!empty($reservations)) {
                $this->appendReservations->execute($reservations);
            }
        } catch (CouldNotSaveException $e) {
            $this->errorLogger->error("Failed to release stock", [
                'order_id' => $magentoOrder->getEntityId(),
                'error' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            $this->errorLogger->error("Unexpected error occurred during stock release", [
                'order_id' => $magentoOrder->getEntityId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retrieve and validate checkout form response
     *
     * @param  string  $token
     * @param  string  $conversationId
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

        $this->utilityHelper->validateSignature($response, $secretKey);

        return $response;
    }
}
