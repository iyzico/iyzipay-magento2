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
use Magento\Framework\Controller\Result\RedirectFactory;
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
        protected RequestInterface $request,
        protected CheckoutSession $checkoutSession,
        protected CustomerSession $customerSession,
        protected ManagerInterface $messageManager,
        protected IyziErrorLogger $errorLogger,
        protected CartRepositoryInterface $quoteRepository,
        protected RedirectFactory $redirectFactory,
        protected ConfigHelper $configHelper,
        protected OrderJobService $orderJobService,
        protected OrderService $orderService,
        protected CardService $cardService,
        protected UtilityHelper $utilityHelper,
        protected QuoteResource $quoteResource,
        protected CartManagementInterface $cartManagement,
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
            "createCsrfValidationException: ".json_encode($params),
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
        $quote = null;

        try {
            $error = [];

            $token = $this->request->getParam('token');

            // Retrieve conversationId from database before validating checkout form
            $conversationId = $this->orderJobService->findConversationIdByToken($token);
            if (!$conversationId) {
                $this->errorLogger->critical(
                    "ConversationId not found for token: $token",
                    ['fileName' => __FILE__, 'lineNumber' => __LINE__]
                );
                $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please contact support.'));
                $resultRedirect = $this->redirectFactory->create();
                return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            }

            $response = $this->orderService->retrieveAndValidateCheckoutForm($token, $conversationId);
            $status = $response->getStatus();
            $paymentStatus = $response->getPaymentStatus();

            if ($status === 'success' && $paymentStatus !== 'FAILURE') {
                $customerId = $this->utilityHelper->getCustomerId($this->customerSession);
                if ($customerId != 0) {
                    $this->cardService->setUserCard($response, $customerId);
                }

                // Get quote from session first, if not available get from token
                $quote = $this->checkoutSession->getQuote();
                $quoteId = $quote->getId();

                // If quote ID is null, try to find it from token (fallback for session issues)
                if (!$quoteId && $token) {
                    $quoteId = $this->orderJobService->findQuoteIdByToken($token);
                    if ($quoteId) {
                        try {
                            $quote = $this->quoteRepository->get($quoteId);
                        } catch (NoSuchEntityException $e) {
                            $this->errorLogger->critical(
                                "Quote not found for quote_id: $quoteId from token: $token",
                                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
                            );
                            $quoteId = null;
                        }
                    }
                }

                // If still no quote ID, cannot create order
                if (!$quoteId) {
                    $this->errorLogger->critical(
                        "Cannot create order: Quote ID not found in session or token: $token",
                        ['fileName' => __FILE__, 'lineNumber' => __LINE__]
                    );
                    $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please contact support.'));
                    $resultRedirect = $this->redirectFactory->create();
                    return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
                }

                // Create order from quote after successful payment (quote-first approach)
                $orderId = $this->orderService->createOrderFromQuote(
                    $quoteId,
                    $this->customerSession,
                    $this->cartManagement,
                    $response
                );

                // Assign order ID to iyzico order job table
                $this->orderJobService->assignOrderIdToIyzicoOrderJob((string)$orderId, (string)$quoteId);

                $resultRedirect = $this->redirectFactory->create();
                return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
            }

            if ($status === 'failure' && $paymentStatus === 'FAILURE') {
                $error['code'] = $response->getErrorCode();
                $error['message'] = $response->getErrorMessage();
                $error['error_group'] = $response->getErrorGroup();
            }

            if (!empty($error)) {
                $this->messageManager->addErrorMessage($error['code']." - ".$error['message']);
            } else {
                $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please try again.'));
            }

            $resultRedirect = $this->redirectFactory->create();
            return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
        } catch (Exception $e) {
            $this->errorLogger->critical(
                "execute error: ".$e->getMessage(),
                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
            );

            $this->messageManager->addErrorMessage(__('An error occurred while processing your payment. Please try again.'));
            $resultRedirect = $this->redirectFactory->create();
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
                "findQuoteById: $quoteId - Message: ".$e->getMessage(),
                ['fileName' => __FILE__, 'lineNumber' => __LINE__]
            );
            return null;
        }
    }

}
