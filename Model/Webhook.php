<?php

namespace Iyzico\Iyzipay\Model;

use Exception;
use Iyzico\Iyzipay\Api\WebhookInterface;
use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Library\Model\CheckoutForm;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\RetrieveCheckoutFormRequest;
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
        protected readonly RequestInterface $request,
        protected readonly ConfigHelper $configHelper,
        protected readonly UtilityHelper $utilityHelper,
        protected readonly OrderService $orderService,
        protected readonly OrderJobService $orderJobService,
        protected readonly IyziWebhookLogger $iyziWebhookLogger
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
            throw new LocalizedException(
                __('Invalid JSON: %1', json_last_error_msg())
            );
        }

        $paymentConversationId = $json['paymentConversationId'] ?? null;
        if (empty($paymentConversationId)) {
            throw new LocalizedException(__('paymentConversationId is missing or empty'));
        }

        $merchantId = $json['merchantId'] ?? null;
        if (empty($merchantId)) {
            throw new LocalizedException(__('merchantId is missing or empty'));
        }

        $token = $json['token'] ?? null;
        if (empty($token)) {
            throw new LocalizedException(__('token is missing or empty'));
        }

        $status = $json['status'] ?? null;
        if (empty($status)) {
            throw new LocalizedException(__('status is missing or empty'));
        }

        $iyziReferenceCode = $json['iyziReferenceCode'] ?? null;
        if (empty($iyziReferenceCode)) {
            throw new LocalizedException(__('iyziReferenceCode is missing or empty'));
        }

        $iyziEventType = $json['iyziEventType'] ?? null;
        if (empty($iyziEventType)) {
            throw new LocalizedException(__('iyziEventType is missing or empty'));
        }

        $iyziEventTime = $json['iyziEventTime'] ?? null;
        if (empty($iyziEventTime)) {
            throw new LocalizedException(__('iyziEventTime is missing or empty'));
        }

        $iyziPaymentId = $json['iyziPaymentId'] ?? null;
        if (empty($iyziPaymentId)) {
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

    public function generateKey(string $secretKey, WebhookData $webhookData): string
    {
        return $secretKey . $webhookData->getIyziEventType() . $webhookData->getIyziPaymentId() . $webhookData->getPaymentConversationId() . $webhookData->getStatus();
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
     * @throws LocalizedException
     * @throws Exception
     */
    public function processWebhook(WebhookData $webhookData): void
    {
        $token = $webhookData->getToken();
        $conversationId = $webhookData->getPaymentConversationId();
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

        $responsePaymentStatus = $response->getPaymentStatus();
        $responsePaymentId = $response->getPaymentId();
        $responseCurrency = $response->getCurrency();
        $responseBasketId = $response->getBasketId();
        $responseConversationId = $response->getConversationId();
        $responsePaidPrice = $response->getPaidPrice();
        $responsePrice = $response->getPrice();
        $responseToken = $response->getToken();
        $responseSignature = $response->getSignature();

        $calculateSignature = $this->utilityHelper->calculateHmacSHA256Signature([
            $responsePaymentStatus,
            $responsePaymentId,
            $responseCurrency,
            $responseBasketId,
            $responseConversationId,
            $responsePaidPrice,
            $responsePrice,
            $responseToken
        ], $secretKey);

        if ($responseSignature !== $calculateSignature) {
            throw new LocalizedException(__('Signature mismatch'));
        }

        $orderId = $this->orderJobService->findParametersByToken($token, 'order_id');

        $this->orderService->updateOrderPaymentStatus($orderId, $response, true);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function processWebhookV3(WebhookData $webhookData): void
    {
        $paymentId = $webhookData->getIyziPaymentId();

        $objectManager = ObjectManager::getInstance();
        $searchCriteriaBuilder = $objectManager->create(SearchCriteriaBuilder::class);
        $orderPaymentRepository = $objectManager->create(OrderPaymentRepositoryInterface::class);

        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('last_trans_id', $paymentId, 'eq')
            ->create();

        $paymentList = $orderPaymentRepository->getList($searchCriteria);

        if ($paymentList->getTotalCount() === 0) {
            throw new LocalizedException(__('Payment record not found for payment ID: %1', $paymentId));
        }

        $payment = $paymentList->getItems()[0];
        $orderId = $payment->getParentId();

        $this->orderService->updateOrderPaymentStatus($orderId, $webhookData);
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
