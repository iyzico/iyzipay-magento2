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

namespace Iyzico\Iyzipay\Model;

use Exception;
use Iyzico\Iyzipay\Api\WebhookInterface;
use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Logger\IyziWebhookLogger;
use Iyzico\Iyzipay\Model\Data\WebhookData;
use Iyzico\Iyzipay\Service\OrderJobService;
use Iyzico\Iyzipay\Service\OrderService;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;

class Webhook implements WebhookInterface
{
    protected string $signatureV3;
    protected WebhookData $webhookData;

    public function __construct(
        protected RequestInterface $request,
        protected ConfigHelper $configHelper,
        protected UtilityHelper $utilityHelper,
        protected OrderService $orderService,
        protected OrderJobService $orderJobService,
        protected IyziWebhookLogger $iyziWebhookLogger
    ) {
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     * @throws Exception
     */
    public function check(string $webhookUrlKey): void
    {
        if ($webhookUrlKey !== $this->configHelper->getWebhookUrlKey()) {
            throw new NotFoundException(__('Webhook URL key not found.'), null, 404);
        }

        $this->signatureV3 = $this->getWebhookHeader();
        $this->webhookData = $this->getWebhookBody();

        $secretKey = $this->configHelper->getSecretKey();
        $key = $this->generateKey($secretKey, $this->webhookData);

        $hmac256Signature = bin2hex(hash_hmac('sha256', $key, $secretKey, true));
        $signatureMatchStatus = $this->validateSignature($this->signatureV3, $hmac256Signature);

        if (!$signatureMatchStatus) {
            $this->processWebhook($this->webhookData);
        } else {
            $this->processWebhookV3($this->webhookData);
        }
    }

    /**
     * @inheritDoc
     */
    public function getWebhookHeader(): string
    {
        return $this->request->getHeader('X-Iyz-Signature-V3');
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function getWebhookBody(): WebhookData
    {
        $webhookData = new WebhookData();

        $content = $this->request->getContent();
        if (empty($content)) {
            throw new LocalizedException(__('Request body is empty'), null, 400);
        }

        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->iyziWebhookLogger->error(
                sprintf(
                    'Invalid JSON: %s',
                    json_last_error_msg()
                )
            );
            throw new LocalizedException(
                __('Invalid JSON: %1', json_last_error_msg())
            );
        }

        $paymentConversationId = $json['paymentConversationId'] ?? null;
        if (empty($paymentConversationId)) {
            $this->iyziWebhookLogger->error(sprintf('paymentConversationId is missing or empty, content: %s',
                $content));
            throw new LocalizedException(__('paymentConversationId is missing or empty'));
        }

        $merchantId = $json['merchantId'] ?? null;
        if (empty($merchantId)) {
            $this->iyziWebhookLogger->error(sprintf('merchantId is missing or empty, content: %s', $content));
            throw new LocalizedException(__('merchantId is missing or empty'));
        }

        $token = $json['token'] ?? null;
        if (empty($token)) {
            $this->iyziWebhookLogger->error(sprintf('token is missing or empty, content: %s', $content));
            throw new LocalizedException(__('token is missing or empty'));
        }

        $status = $json['status'] ?? null;
        if (empty($status)) {
            $this->iyziWebhookLogger->error(sprintf('status is missing or empty, content: %s', $content));
            throw new LocalizedException(__('status is missing or empty'));
        }

        $iyziReferenceCode = $json['iyziReferenceCode'] ?? null;
        if (empty($iyziReferenceCode)) {
            $this->iyziWebhookLogger->error(sprintf('iyziReferenceCode is missing or empty, content: %s', $content));
            throw new LocalizedException(__('iyziReferenceCode is missing or empty'));
        }

        $iyziEventType = $json['iyziEventType'] ?? null;
        if (empty($iyziEventType)) {
            $this->iyziWebhookLogger->error(sprintf('iyziEventType is missing or empty, content: %s', $content));
            throw new LocalizedException(__('iyziEventType is missing or empty'));
        }

        $iyziEventTime = $json['iyziEventTime'] ?? null;
        if (empty($iyziEventTime)) {
            $this->iyziWebhookLogger->error(sprintf('iyziEventTime is missing or empty, content: %s', $content));
            throw new LocalizedException(__('iyziEventTime is missing or empty'));
        }

        $iyziPaymentId = $json['iyziPaymentId'] ?? null;
        if (empty($iyziPaymentId)) {
            $this->iyziWebhookLogger->error(sprintf('iyziPaymentId is missing or empty, content: %s', $content));
            throw new LocalizedException(__('iyziPaymentId is missing or empty'));
        }

        $webhookData->setPaymentConversationId(strip_tags((string) $paymentConversationId));
        $webhookData->setMerchantId((int) $merchantId);
        $webhookData->setToken(strip_tags((string) $token));
        $webhookData->setStatus(strip_tags((string) $status));
        $webhookData->setIyziReferenceCode(strip_tags((string) $iyziReferenceCode));
        $webhookData->setIyziEventType(strip_tags((string) $iyziEventType));
        $webhookData->setIyziEventTime((int) $iyziEventTime);
        $webhookData->setIyziPaymentId((int) $iyziPaymentId);

        return $webhookData;
    }

    /**
     * @inheritDoc
     */
    public function generateKey(string $secretKey, WebhookData $webhookData): string
    {
        return $secretKey.$webhookData->getIyziEventType().$webhookData->getIyziPaymentId().$webhookData->getPaymentConversationId().$webhookData->getStatus();
    }

    /**
     * @inheritDoc
     */
    public function validateSignature(string $signature, string $payload): bool
    {
        return hash_equals($signature, $payload);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function processWebhook(WebhookData $webhookData): void
    {
        try {
            $token = $webhookData->getToken();
            $conversationId = $webhookData->getPaymentConversationId();
            $response = $this->orderService->retrieveAndValidateCheckoutForm($token, $conversationId);
            $orderId = $this->orderJobService->findParametersByToken($token, 'order_id');
            $this->orderService->updateOrderPaymentStatus($orderId, $response, 'yes');
        } catch (Exception $e) {
            $this->iyziWebhookLogger->error(sprintf('Webhook process error: %s', $e->getMessage()));
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function processWebhookV3(WebhookData $webhookData): void
    {
        try {
            $paymentId = $webhookData->getIyziPaymentId();

            $objectManager = ObjectManager::getInstance();
            $searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);
            $orderPaymentRepository = $objectManager->create(OrderPaymentRepositoryInterface::class);

            $searchCriteria = $searchCriteriaBuilder
                ->addFilter('last_trans_id', $paymentId)
                ->create();

            $paymentList = $orderPaymentRepository->getList($searchCriteria);

            if ($paymentList->getTotalCount() === 0) {
                throw new LocalizedException(__('Payment record not found for payment ID: %1', $paymentId));
            }

            $payment = $paymentList->getItems()[0];
            $orderId = $payment->getParentId();

            $this->orderService->updateOrderPaymentStatus($orderId, $webhookData, 'v3');
        } catch (Exception $e) {
            $this->iyziWebhookLogger->error(sprintf('Webhook process v3 error: %s', $e->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function logWebhookEvent(string $eventType, array $data, string $status): void
    {
        $this->iyziWebhookLogger->info(
            sprintf(
                'Webhook event: %s, Status: %s, Data: %s',
                $eventType,
                $status,
                json_encode($data)
            )
        );
    }
}
