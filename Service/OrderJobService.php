<?php

namespace Iyzico\Iyzipay\Service;

use Exception;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Model\IyziOrderJobFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob as IyziOrderJobResource;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob\CollectionFactory as IyziOrderJobCollectionFactory;
use Throwable;

readonly class OrderJobService
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

            if ($status == 'processing' || $status == 'canceled') {
                $this->iyziOrderJobResource->delete($iyzicoOrderJob);
            } else {
                $this->iyziOrderJobResource->save($iyzicoOrderJob);
            }
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
     *
     * @param  string  $token
     * @param  string  $find
     * @return mixed
     */
    public function findParametersByToken(string $token, string $find): mixed
    {
        $iyzicoOrderJob = $this->iyziOrderJobFactory->create();
        $this->iyziOrderJobResource->load($iyzicoOrderJob, $token, 'iyzico_payment_token');
        return $iyzicoOrderJob->getData($find);
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
