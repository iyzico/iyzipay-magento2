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

namespace Iyzico\Iyzipay\Controller\Response;

use Exception;
use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Helper\UtilityHelper;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Service\CardService;
use Iyzico\Iyzipay\Service\OrderJobService;
use Iyzico\Iyzipay\Service\OrderService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

class IyzipayResponse implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        protected readonly RequestInterface $request,
        protected readonly CheckoutSession $checkoutSession,
        protected readonly CustomerSession $customerSession,
        protected readonly ManagerInterface $messageManager,
        protected readonly IyziErrorLogger $errorLogger,
        protected readonly CartRepositoryInterface $quoteRepository,
        protected readonly ResultFactory $resultFactory,
        protected readonly ConfigHelper $configHelper,
        protected readonly OrderJobService $orderJobService,
        protected readonly OrderService $orderService,
        protected readonly CardService $cardService,
        protected readonly UtilityHelper $utilityHelper,
        protected readonly QuoteResource $quoteResource,
        protected readonly CartManagementInterface $cartManagement,
    ) {
    }

    /**
     * Create Csrf Validation Exception
     *
     * This function is responsible for creating the csrf validation exception.
     *
     * @param  RequestInterface  $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $params = $request->getParams();
        $this->errorLogger->critical(
            "createCsrfValidationException: " . json_encode($params),
            ['fileName' => __FILE__, 'lineNumber' => __LINE__]
        );
        return null;
    }

    /**
     * Validate For Csrf
     *
     * This function is responsible for validating the csrf.
     *
     * @param  RequestInterface  $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        try {
            $token = $this->request->getParam('token');

            $orderId = $this->orderJobService->findParametersByToken($token, 'order_id');
            $quoteId = $this->orderJobService->findParametersByToken($token, 'quote_id');
            $conversationId = $this->orderJobService->findParametersByToken($token, 'iyzico_conversation_id');

            $quote = $this->findQuoteById($quoteId);
            $order = $this->orderService->findOrderById($orderId);

            if ($quote == null) {
                $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please try again.'));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            }

            if ($order == null) {
                $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please try again.'));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            }

            $response = $this->orderService->retrieveAndValidateCheckoutForm($token, $conversationId);
            $status = $response->getStatus();
            $paymentStatus = $response->getPaymentStatus();

            $this->orderService->updateOrderPaymentStatus($orderId, $response);

            if ($status === 'success' && $paymentStatus !== 'FAILURE') {
                $customerId = $this->utilityHelper->getCustomerId($this->customerSession);
                if ($customerId != 0) {
                    $this->cardService->setUserCard($response, $customerId);
                }

                $this->checkoutSession->setLastQuoteId($quoteId);
                $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->checkoutSession->setLastOrderId($order->getId());
                $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->checkoutSession->setLastOrderStatus($order->getStatus());

                $quote = $this->checkoutSession->getQuote();
                $quote->setIsActive(false);

                try {
                    $this->quoteResource->save($quote);
                } catch (Exception $e) {
                    $this->errorLogger->critical("Quote save error: " . $e->getMessage());
                    throw new LocalizedException(__('Quote could not be saved.'));
                }

                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
            } else {
                $this->orderService->releaseStock($order);
                $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please try again.'));
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            }
        } catch (Exception $e) {
            $this->errorLogger->critical(
                "execute error: " . $e->getMessage(),
                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
            );
            $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please try again.'));
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
        }
    }

    /**
     * Find Quote By Id
     *
     * This function is responsible for finding the quote by id.
     *
     * @param  string  $quoteId
     * @return CartInterface|Quote|null
     */
    private function findQuoteById(string $quoteId): CartInterface|Quote|null
    {
        try {
            return $this->quoteRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->errorLogger->critical(
                "findQuoteById: $quoteId - Message: " . $e->getMessage(),
                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
            );
            return null;
        }
    }
}
