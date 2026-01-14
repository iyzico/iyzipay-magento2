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

namespace Iyzico\Iyzipay\Controller\Redirect;

use Iyzico\Iyzipay\Service\OneTimeUrlService;
use Iyzico\Iyzipay\Service\OrderJobService;
use Iyzico\Iyzipay\Service\OrderService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderRepositoryInterface;

class BackToStore implements HttpGetActionInterface
{
    public function __construct(
        protected RedirectFactory $redirectFactory,
        protected QuoteRepository $quoteRepository,
        protected OrderRepositoryInterface $orderRepository,
        protected RequestInterface $request,
        protected OrderService $orderService,
        protected OrderJobService $orderJobService,
        protected OneTimeUrlService $oneTimeUrlService
    ) {
    }

    /**
     * Execute action based on request and return result
     *
     * @return Redirect
     * @throws NoSuchEntityException
     */
    public function execute(): Redirect
    {
        $token = $this->request->getParam('token');

        // Try to get quote_id from one-time URL service first
        $quoteId = $this->oneTimeUrlService->validateAndGetBasketId($token);

        // If not found, try to get quote_id from order job service (quote-first approach)
        if (!$quoteId) {
            $quoteId = $this->orderJobService->findQuoteIdByToken($token);
        }

        if ($quoteId) {
            try {
                $quote = $this->quoteRepository->get($quoteId);

                // Check if order was created (quote-first approach)
                $orderId = $this->orderJobService->findOrderIdByQuoteId((int)$quoteId);

                if ($orderId) {
                    // Order exists, cancel it (Magento will automatically release stock)
                    try {
                        $order = $this->orderRepository->get($orderId);
                        $order->setState('canceled')->setStatus('canceled');
                        $order->addCommentToStatusHistory(__('Order canceled - customer returned from payment page'));

                        $this->orderJobService->removeIyziOrderJobTable((int)$orderId);

                        $this->orderRepository->save($order);
                    } catch (NoSuchEntityException $e) {
                        // Order not found, continue to activate quote
                    }
                }

                // Activate quote so customer can continue shopping
                $quote->setIsActive(1);
                $this->quoteRepository->save($quote);
            } catch (NoSuchEntityException $e) {
                // Quote not found, continue to redirect
            }
        }

        $redirect = $this->redirectFactory->create();
        $redirect->setPath('checkout/cart');
        return $redirect;
    }
}
