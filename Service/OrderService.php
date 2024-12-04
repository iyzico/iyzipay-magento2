<?php

namespace Iyzico\Iyzipay\Service;

use Exception;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class OrderService
{

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly QuoteManagement $quoteManagement,
        private readonly QuoteRepository $quoteRepository,
        private readonly Quote $quoteResource,
        private readonly UtilityHelper $utilityHelper,
        private readonly IyziErrorLogger $errorLogger,
        private readonly OrderJobService $orderJobService
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
        $order->setCanSendNewEmailFlag(false);

        $this->orderRepository->save($order);

        return $orderId;
    }

    /**
     * Update Order Payment Status
     *
     * This function is responsible for updating the order payment status based on the response.
     *
     * @param  string  $orderId
     * @param  $response
     * @param  bool  $isWebhook
     * @return void
     * @throws Exception
     */
    public function updateOrderPaymentStatus(string $orderId, $response, bool $isWebhook = false): void
    {
        $order = $this->findOrderById($orderId);
        $payment = $order->getPayment();

        $paymentStatus = $response->getPaymentStatus();
        $status = $response->getStatus();

        if (!$isWebhook) {
            $payment->setLastTransId($response->getPaymentId());
            $paymentAdditionalInformation = [
                'method_title' => 'Kredi/Banka Kartı ile Ödeme',
                'iyzico_payment_id' => $response->getPaymentId(),
                'iyzico_conversation_id' => $response->getConversationId(),
            ];

            $payment->setAdditionalInformation($paymentAdditionalInformation);

            $payment->setTransactionId($response->getPaymentId())
                ->setIsTransactionClosed(0)
                ->setTransactionAdditionalInfo(
                    Transaction::RAW_DETAILS,
                    json_encode($paymentAdditionalInformation)
                );
        }

        if ($paymentStatus == 'PENDING_CREDIT' && $status == 'success') {
            $order->setState("pending_payment")->setStatus("pending_payment");
            $order->addCommentToStatusHistory(__("PENDING_CREDIT"));
            $this->orderJobService->setOrderJobStatus($orderId, "pending_payment");
        }

        if ($paymentStatus == 'INIT_BANK_TRANSFER' && $status == 'success') {
            $order->setState("pending_payment")->setStatus("pending_payment");
            $order->addCommentToStatusHistory(__("INIT_BANK_TRANSFER"));
            $this->orderJobService->setOrderJobStatus($orderId, "pending_payment");
        }

        if ($paymentStatus == 'SUCCESS' && $status == 'success') {
            $order->setState("processing")->setStatus("processing");
            $order->addCommentToStatusHistory(__("SUCCESS"));
            $this->orderJobService->setOrderJobStatus($orderId, "processing");
        }

        if ($response->getInstallment() > 1) {
            $order = $this->setOrderInstallmentFee($order, $response->getPaidPrice(), $response->getInstallment());
        }

        if (!$isWebhook) {
            $order->addCommentToStatusHistory("Payment ID: ".$response->getPaymentId()." - Conversation ID:".$response->getConversationId());
        }

        $order->setCanSendNewEmailFlag(true);
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
     *
     * @param $order
     * @param $paidPrice
     * @param $installment
     * @return mixed
     */
    public function setOrderInstallmentFee($order, $paidPrice, $installment): mixed
    {
        $grandTotal = $order->getGrandTotal();

        $installmentPrice = $this->utilityHelper->calculateInstallmentPrice($paidPrice, $grandTotal);

        $order->setInstallmentFee($installmentPrice);
        $order->setInstallmentCount($installment);

        return $order;
    }

    /**
     * Cancel Order
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
}
