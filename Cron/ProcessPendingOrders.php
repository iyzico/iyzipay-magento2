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

namespace Iyzico\Iyzipay\Cron;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Library\Model\CheckoutForm;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\RetrieveCheckoutFormRequest;
use Iyzico\Iyzipay\Logger\IyziCronLogger;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob\Collection;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob\CollectionFactory;
use Iyzico\Iyzipay\Service\OrderService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;


class ProcessPendingOrders
{
    protected const PAGE_SIZE = 100;
    protected const IYZICO_TOKEN_EXPIRATION_MIN = 30;

    public function __construct(
        protected readonly IyziCronLogger $cronLogger,
        protected readonly Collection $collection,
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly StoreManagerInterface $storeManager,
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly CollectionFactory $collectionFactory,
        protected readonly ConfigHelper $configHelper,
        protected readonly OrderService $orderService
    ) {
    }

    public function execute()
    {
        try {
            $this->cronLogger->info('iyzico cron job started');

            $page = 1;
            $processedCount = 0;
            $totalPages = $this->getTotalPages();

            $ordersToDelete = $this->getOrdersToDelete($page);
            if (!empty($ordersToDelete)) {
                $this->deleteProcessedOrders($ordersToDelete);
            }

            while ($page <= $totalPages) {
                $orders = $this->getPageOfOrders($page);
                $ordersCount = count($orders);

                if ($ordersCount > 0) {
                    $this->processOrders($orders);
                    $processedCount += $ordersCount;
                }

                $this->cronLogger->info("Processed batch", [
                    'page' => $page,
                    'processed_count' => $ordersCount,
                    'total_processed' => $processedCount
                ]);

                $page++;
            }

            $this->cronLogger->info('iyzico cron job completed', ['total_processed' => $processedCount]);

            return ['success' => true, 'message' => "Processed $processedCount orders"];

        } catch (Exception $e) {
            $this->cronLogger->error('iyzico cron job failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Summary of getTotalPages
     * @return float
     */
    private function getTotalPages(): float
    {
        $totalItems = $this->collection->getSize();

        return ceil($totalItems / self::PAGE_SIZE);
    }


    /**
     * Summary of getOrdersToDelete
     * @return float
     */
    private function getOrdersToDelete($page): array
    {
        $this->collection
            ->addFieldToFilter('status', ['in' => ['processing', 'canceled']])
            ->setPageSize(self::PAGE_SIZE)
            ->setCurPage($page);

        return $this->collection->getAllIds();
    }

    private function getPageOfOrders($page): array
    {
        $this->cronLogger->info('Fetching orders', ['page' => $page]);

        $this->collection
            ->addFieldToFilter('status', ['in' => ['pending_payment', 'received']])
            ->setPageSize(self::PAGE_SIZE)
            ->setCurPage($page);

        return $this->collection->getItems();
    }

    private function processOrders($orders): void
    {
        $this->cronLogger->info('Processing orders', ['count' => count($orders)]);
        $promises = [];
        $client = new Client();

        foreach ($orders as $order) {
            if (!$this->shouldProcessOrder($order)) {
                continue;
            }

            $token = $order->getIyzicoPaymentToken();
            $conversationId = $order->getIyzicoConversationId();
            $promises[$order->getId()] = $this->getPaymentDetailAsync($token, $conversationId);
        }

        $responses = Utils::settle($promises)->wait();

        foreach ($orders as $order) {
            if (!isset($responses[$order->getId()]) || $responses[$order->getId()]['state'] !== 'fulfilled') {
                continue;
            }

            $response = $responses[$order->getId()]['value'];
            $responseBody = json_decode($response->getRawResult());

            $conversationId = $order->getIyzicoConversationId();
            $responseConversationId = $responseBody->conversationId ?? '0';

            if (!$this->validateConversationId($conversationId, $responseConversationId)) {
                continue;
            }

            $order = $this->updateOrder($order, $responseBody);
            $order->save();

            if ($this->shouldCancelOrder($order, $responseBody)) {
                $this->cancelOrder($order);
            }
        }
    }

    /**
     * Retrieve Payment Detail Asynchronously
     *
     * This function is responsible for retrieving the payment detail asynchronously.
     *
     * @param  string  $token
     * @param  string  $conversationId
     * @return CheckoutForm
     * @throws LocalizedException
     */
    private function getPaymentDetailAsync(string $token, string $conversationId): CheckoutForm
    {
        $locale = $this->configHelper->getLocale();

        $request = new RetrieveCheckoutFormRequest();
        $request->setLocale($locale);
        $request->setConversationId($conversationId);
        $request->setToken($token);

        $options = new Options();
        $options->setBaseUrl($this->configHelper->getBaseUrl());
        $options->setApiKey($this->configHelper->getApiKey());
        $options->setSecretKey($this->configHelper->getSecretKey());

        $response = CheckoutForm::retrieve($request, $options);

        return $response;
    }

    private function validateConversationId(string $conversationId, string $responseConversationId): bool
    {
        if ($conversationId !== $responseConversationId) {
            $this->cronLogger->error('Conversation ID does not match', [
                'conversation_id' => $conversationId,
                'response_conversation_id' => $responseConversationId
            ]);

            return false;
        }

        return true;
    }

    private function updateOrder($order, $responseBody): mixed
    {
        $paymentStatus = $responseBody->paymentStatus ?? '';
        $status = $responseBody->status ?? '';

        $mapping = $this->mapping($paymentStatus, $status);

        if (empty($mapping)) {
            $this->cronLogger->error('Mapping not found', [
                'payment_status' => $paymentStatus,
                'status' => $status,
                'order_id' => $order->getOrderId()
            ]);

            return $order;
        }

        $order->setStatus($mapping['status']);

        $magentoOrder = $this->orderRepository->get($order->getOrderId());
        $magentoOrder->setState($mapping['state']);
        $magentoOrder->setStatus($mapping['status']);
        $magentoOrder->addStatusHistoryComment($mapping['comment']);

        $magentoOrder->save();

        $this->cronLogger->info('Order status updated', [
            'order_id' => $order->getOrderId(),
            'state' => $mapping['state'],
            'status' => $mapping['status'],
            'comment' => $mapping['comment']
        ]);

        return $order;
    }

    private function mapping(string $paymentStatus, string $status): array
    {
        if ($status == "failure" && $paymentStatus != '')
            return ['state' => "canceled", 'status' => "canceled", 'comment' => __("CANCELLED_ORDER")];

        if ($paymentStatus == 'INIT_THREEDS' && $status == 'success')
            return ['state' => "pending_payment", 'status' => "pending_payment", 'comment' => __("INIT_THREEDS_CRON")];

        if ($paymentStatus == 'SUCCESS' && $status == 'success')
            return ['state' => "processing", 'status' => "processing", 'comment' => __("SUCCESS")];

        if ($paymentStatus == 'INIT_BANK_TRANSFER' && $status == 'success')
            return ['state' => "pending_payment", 'status' => "pending_payment", 'comment' => __("INIT_BANK_TRANSFER_CRON")];

        if ($paymentStatus == 'PENDING_CREDIT' && $status == 'success')
            return ['state' => "pending_payment", 'status' => "pending_payment", 'comment' => __("PENDING_CREDIT")];

        return [];
    }

    private function deleteProcessedOrders($orderIds): void
    {
        if (empty($orderIds)) {
            return;
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('id', ['in' => $orderIds]);

        $deletedCount = $collection->getSize();
        $collection->walk('delete');

        $this->cronLogger->info("Bulk delete completed", ['deleted_count' => $deletedCount]);
    }

    private function isPaymentExpired($order): bool
    {
        $expirationTime = strtotime($order->getCreatedAt()) + self::IYZICO_TOKEN_EXPIRATION_MIN;
        return time() > $expirationTime;
    }

    private function shouldProcessOrder($order): bool
    {
        if (!$this->isPaymentExpired($order)) {
            return false;
        }

        return true;
    }

    private function shouldCancelOrder($order, $responseBody): bool
    {
        $paymentStatus = $responseBody->paymentStatus ?? '';
        $status = $responseBody->status ?? '';

        $cancelConditions = [
            ($status == 'failure' && $paymentStatus != ''),
            ($paymentStatus == 'INIT_BANK_TRANSFER' && $this->isBankTransferExpired($order)),
            ($paymentStatus == 'PENDING_CREDIT' && $this->isOrderTooOld($order))
        ];

        return in_array(true, $cancelConditions);
    }

    private function cancelOrder($order): void
    {
        try {
            $magentoOrder = $this->orderRepository->get($order->getOrderId());
            $this->orderService->releaseStock($magentoOrder);

            $magentoOrder->setState("canceled");
            $magentoOrder->setStatus("canceled");
            $magentoOrder->addStatusHistoryComment(__("Order automatically canceled by iyzico payment gateway"));

            $this->orderRepository->save($magentoOrder);

            $this->cronLogger->info('Order canceled', [
                'order_id' => $order->getOrderId(),
                'reason' => 'Payment not completed'
            ]);
        } catch (Exception $e) {
            $this->cronLogger->error('Failed to cancel order', [
                'order_id' => $order->getOrderId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function isBankTransferExpired($order): bool
    {
        $waitingPeriod = 24;

        $orderDate = strtotime($order->getCreatedAt());
        $expirationTime = $orderDate + ($waitingPeriod * 3600);

        return time() > $expirationTime;
    }

    private function isOrderTooOld($order, $maxAgeHours = 72): bool
    {
        $orderDate = strtotime($order->getCreatedAt());
        $expirationTime = $orderDate + ($maxAgeHours * 3600);
        return time() > $expirationTime;
    }

}