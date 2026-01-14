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
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Model\IyziOrderJobFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob as IyziOrderJobResource;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob\CollectionFactory as IyziOrderJobCollectionFactory;
use Throwable;

class OrderJobService
{
    public function __construct(
        protected IyziOrderJobFactory $iyziOrderJobFactory,
        protected IyziOrderJobResource $iyziOrderJobResource,
        protected IyziOrderJobCollectionFactory $iyziOrderJobCollectionFactory,
        protected IyziErrorLogger $errorLogger,
    ) {
    }

    /**
     * Set Iyzipay Order Job
     *
     * This function is responsible for saving the iyzi order job.
     *
     * @param  string  $orderId
     * @param  string  $status
     * @return void
     */
    public function setOrderJobStatus(string $orderId, string $status): void
    {
        try {
            $collection = $this->iyziOrderJobCollectionFactory->create();
            $iyzicoOrderJob = $collection->addFieldToFilter('order_id', $orderId)->getFirstItem();

            if (!$iyzicoOrderJob->getId()) {
                $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
                $iyzicoOrderJob->setOrderId($orderId);
            }

            $iyzicoOrderJob->setStatus($status);
            $this->iyziOrderJobResource->save($iyzicoOrderJob);
        } catch (Throwable $th) {
            $this->errorLogger->critical("setOrderJobStatus: ".$th->getMessage(), [
                'fileName' => __FILE__,
                'lineNumber' => __LINE__,
            ]);
        }
    }

    /**
     * Set Iyzipay Order Job
     *
     * This function is responsible for saving the iyzi order job.
     *
     * @param  string  $orderId
     * @param  string  $quoteId
     * @return void
     */
    public function assignOrderIdToIyzicoOrderJob(string $orderId, string $quoteId): void
    {
        try {
            $collection = $this->iyziOrderJobCollectionFactory->create();
            $iyzicoOrderJob = $collection->addFieldToFilter('quote_id', $quoteId)->getFirstItem();
            $iyzicoOrderJob->setOrderId($orderId);
            $this->iyziOrderJobResource->save($iyzicoOrderJob);
        } catch (Throwable $th) {
            $this->errorLogger->critical("assignOrderIdToIyzicoOrderJob: ".$th->getMessage(), [
                'fileName' => __FILE__,
                'lineNumber' => __LINE__,
            ]);
        }
    }

    /**
     * Find Parameters By Token
     *
     * This function is responsible for finding the parameters by token.
     * In quote-first approach, order_id may be null if order hasn't been created yet.
     *
     * @param  string  $token
     * @param  string  $find
     * @return mixed
     * @throws Exception
     */
    public function findParametersByToken(string $token, string $find): mixed
    {
        try {
            $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
            $this->iyziOrderJobResource->load($iyzicoOrderJob, $token, 'iyzico_payment_token');
            $data = $iyzicoOrderJob->getData($find);
            // Return null if order_id is requested but not set yet (quote-first approach)
            if ($find === 'order_id' && empty($data)) {
                return null;
            }
            return $data;
        } catch (Throwable $th) {
            $this->errorLogger->critical("findParametersByToken: ".$th->getMessage(), [
                'fileName' => __FILE__,
                'lineNumber' => __LINE__,
            ]);
            throw new Exception($th->getMessage());
        }
    }

    /**
     * Find Quote ID By Token
     *
     * This function is responsible for finding the quote ID by token.
     * Used in quote-first payment flow to get quote_id when order hasn't been created yet.
     *
     * @param  string  $token
     * @return int|null
     */
    public function findQuoteIdByToken(string $token): ?int
    {
        try {
            $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
            $this->iyziOrderJobResource->load($iyzicoOrderJob, $token, 'iyzico_payment_token');
            $quoteId = $iyzicoOrderJob->getQuoteId();
            return $quoteId ? (int)$quoteId : null;
        } catch (Throwable $th) {
            $this->errorLogger->critical("findQuoteIdByToken: ".$th->getMessage(), [
                'fileName' => __FILE__,
                'lineNumber' => __LINE__,
            ]);
            return null;
        }
    }

    /**
     * Find Conversation ID By Token
     *
     * This function is responsible for finding the conversation ID by token.
     * Used to retrieve conversationId from database before validating checkout form.
     *
     * @param  string  $token
     * @return string|null
     */
    public function findConversationIdByToken(string $token): ?string
    {
        try {
            $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
            $this->iyziOrderJobResource->load($iyzicoOrderJob, $token, 'iyzico_payment_token');
            $conversationId = $iyzicoOrderJob->getIyzicoConversationId();
            return $conversationId ? (string)$conversationId : null;
        } catch (Throwable $th) {
            $this->errorLogger->critical("findConversationIdByToken: ".$th->getMessage(), [
                'fileName' => __FILE__,
                'lineNumber' => __LINE__,
            ]);
            return null;
        }
    }

    /**
     * Find OrderId By QuoteId
     *
     * This function is responsible for finding the order id by quote id.
     *
     * @param  int  $quoteId
     * @return mixed
     */
    public function findOrderIdByQuoteId(int $quoteId): mixed
    {
        $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
        $this->iyziOrderJobResource->load($iyzicoOrderJob, $quoteId, 'quote_id');
        return $iyzicoOrderJob->getOrderId();
    }

    /**
     * Save Iyzi Order Table
     *
     * This function is responsible for saving the iyzi order table.
     *
     * @param  $response
     * @param  int  $quoteId
     * @param  int|null  $orderId
     */
    public function saveIyziOrderJobTable($response, int $quoteId, int|null $orderId): void
    {
        try {
            $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
            $this->iyziOrderJobResource->load($iyzicoOrderJob, $quoteId, 'quote_id');

            $iyzicoOrderJob->setQuoteId($quoteId);
            $iyzicoOrderJob->setOrderId($orderId);
            $iyzicoOrderJob->setIyzicoPaymentToken($response->getToken());
            $iyzicoOrderJob->setIyzicoConversationId($response->getConversationId());
            $iyzicoOrderJob->setStatus('pending_payment');

            $this->iyziOrderJobResource->save($iyzicoOrderJob);
        } catch (Exception $e) {
            $this->errorLogger->critical($e->getMessage());
        }
    }

    /**
     * Save Iyzi Order Job Table For Quote
     *
     * This function is responsible for saving the iyzi order job table with quote information only.
     * Used in quote-first payment flow where order is created after payment success.
     * Order ID will be null until order is created.
     *
     * @param  string  $token
     * @param  string  $conversationId
     * @param  int  $quoteId
     * @return void
     */
    public function saveIyziOrderJobTableForQuote(string $token, string $conversationId, int $quoteId): void
    {
        try {
            $collection = $this->iyziOrderJobCollectionFactory->create();
            $iyzicoOrderJob = $collection->addFieldToFilter('quote_id', $quoteId)->getFirstItem();

            // If record doesn't exist, create new one
            if (!$iyzicoOrderJob->getId()) {
                $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
            }

            $iyzicoOrderJob->setQuoteId($quoteId);
            $iyzicoOrderJob->setIyzicoPaymentToken($token);
            $iyzicoOrderJob->setIyzicoConversationId($conversationId);
            $iyzicoOrderJob->setStatus('pending_payment');
            // Order ID remains null until order is created

            $this->iyziOrderJobResource->save($iyzicoOrderJob);
        } catch (Throwable $th) {
            $this->errorLogger->critical("saveIyziOrderJobTableForQuote: ".$th->getMessage(), [
                'fileName' => __FILE__,
                'lineNumber' => __LINE__,
            ]);
        }
    }

    /**
     * Remove Iyzi Order Table
     *
     * This function is responsible for removing the iyzi order table.
     *
     * @param  int  $orderId
     * @return void
     */
    public function removeIyziOrderJobTable(int $orderId): void
    {
        try {
            $iyziOrderJob = $this->iyziOrderJobFactory->create();
            $iyziOrderJob->load($orderId, 'order_id');
            $iyziOrderJob->delete();
        } catch (Exception $e) {
            $this->errorLogger->critical($e->getMessage());
        }
    }
}
