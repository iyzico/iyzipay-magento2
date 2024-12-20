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

use Exception;
use GuzzleHttp\Promise\Utils;
use Iyzico\Iyzipay\Logger\IyziCronLogger;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob\Collection;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob\CollectionFactory;
use Iyzico\Iyzipay\Service\OrderService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

readonly class ProcessPendingOrderHelper
{
    protected const IYZICO_TOKEN_EXPIRATION_MIN = 30;
    protected const PAGE_SIZE = 100;

    public function __construct(
        protected Collection $collection,
        protected CollectionFactory $collectionFactory,
        protected OrderRepositoryInterface $orderRepository,
        protected OrderManagementInterface $orderManagement,
        protected OrderService $orderService,
        protected ConfigHelper $configHelper,
        protected UtilityHelper $utilityHelper,
        protected IyziCronLogger $cronLogger
    ) {
    }

    /**
     * Get Total Pages
     *
     * This function is responsible for getting the total pages.
     *
     * @return float
     */
    public function getTotalPages(): float
    {
        return ceil($this->collection->getSize() / self::PAGE_SIZE);
    }

    /**
     * Get OrderIds To Delete
     *
     * This function is responsible for getting the order ids to delete.
     *
     * @param $page
     * @return array
     */
    public function getOrdersToDelete($page): array
    {
        $this->collection
            ->addFieldToFilter('status', ['in' => ['processing', 'canceled']])
            ->setPageSize(self::PAGE_SIZE)
            ->setCurPage($page);

        return $this->collection->getAllIds();
    }

    /**
     * Get Orders By Page
     *
     * This function is responsible for getting the orders by page.
     *
     * @param $page
     * @return array
     */
    public function getPageOfOrders($page): array
    {
        $this->cronLogger->info('Fetching orders', ['page' => $page]);
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => ['pending_payment', 'received']])
            ->setPageSize(self::PAGE_SIZE)->setCurPage($page);
        return $collection->getItems();
    }

    /**
     * Process Orders
     *
     * This function is responsible for processing the orders.
     *
     * @throws LocalizedException
     */
    public function processOrders($orders): void
    {
        $this->cronLogger->info('Processing orders', ['count' => count($orders)]);
        $promises = [];

        foreach ($orders as $order) {
            if (!$this->shouldProcessOrder($order)) {
                continue;
            }

            $token = $order->getIyzicoPaymentToken();
            $conversationId = $order->getIyzicoConversationId();
            $promises[$order->getId()] = $this->orderService->retrieveAndValidateCheckoutForm($token, $conversationId);
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

            if (!$this->utilityHelper->validateConversationId($conversationId, $responseConversationId)) {
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
     * Should Process Order
     *
     * This function is responsible for checking if the order should be processed.
     *
     * @param $order
     * @return bool
     */
    private function shouldProcessOrder($order): bool
    {
        if (!$this->isPaymentExpired($order)) {
            return false;
        }

        return true;
    }

    /**
     * Is Payment Expired
     *
     * This function is responsible for checking if the payment is expired.
     *
     * @param $order
     * @return bool
     */
    private function isPaymentExpired($order): bool
    {
        $expirationTime = strtotime($order->getCreatedAt()) + self::IYZICO_TOKEN_EXPIRATION_MIN;
        return time() > $expirationTime;
    }

    /**
     * Update Order
     *
     * This function is responsible for updating the order.
     *
     * @param $order
     * @param $responseBody
     * @return mixed
     * @throws Exception
     */
    private function updateOrder($order, $responseBody): mixed
    {
        $paymentStatus = $responseBody->paymentStatus ?? '';
        $status = $responseBody->status ?? '';

        $mapping = $this->utilityHelper->findOrderByPaymentAndStatus($paymentStatus, $status);

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

    /**
     * Should Cancel Order
     *
     * This function is responsible for checking if the order should be canceled.
     *
     * @param $order
     * @param $responseBody
     * @return bool
     */
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

    /**
     * Is Bank Transfer Expired
     *
     * This function is responsible for checking if the bank transfer is expired.
     *
     * @param $order
     * @return bool
     */
    private function isBankTransferExpired($order): bool
    {
        $waitingPeriod = 24;

        $orderDate = strtotime($order->getCreatedAt());
        $expirationTime = $orderDate + ($waitingPeriod * 3600);

        return time() > $expirationTime;
    }

    /**
     * Is Order Too Old
     *
     * This function is responsible for checking if the order is too old.
     *
     * @param $order
     * @param  int  $maxAgeHours
     * @return bool
     */
    private function isOrderTooOld($order, int $maxAgeHours = 72): bool
    {
        $orderDate = strtotime($order->getCreatedAt());
        $expirationTime = $orderDate + ($maxAgeHours * 3600);
        return time() > $expirationTime;
    }

    /**
     * Cancel Order
     *
     * This function is responsible for canceling the order.
     *
     * @param $order
     * @return void
     */
    private function cancelOrder($order): void
    {
        if ($order->getOrderId()) {
            try {
                $this->orderManagement->cancel($order->getOrderId());
                $magentoOrder = $this->orderRepository->get($order->getOrderId());
                $magentoOrder->addStatusHistoryComment(__("AUTO_CANCEL"));
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
    }

    /**
     * Delete Processed Orders
     *
     * This function is responsible for deleting the processed orders.
     *
     * @param $orderIds
     * @return void
     */
    public function deleteProcessedOrders($orderIds): void
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
}
