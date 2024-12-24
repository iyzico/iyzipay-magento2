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
        $quoteId = $this->oneTimeUrlService->validateAndGetBasketId($token);

        if ($quoteId) {
            $quote = $this->quoteRepository->get($quoteId);
            $orderId = $quote->getReservedOrderId();
            if ($orderId) {
                $order = $this->orderRepository->get($orderId);
                $order->setState('canceled')->setStatus('canceled');

                $this->orderService->releaseStock($order);
                $this->orderJobService->removeIyziOrderJobTable($orderId);

                $this->orderRepository->save($order);

                $quote->setIsActive(1);
                $this->quoteRepository->save($quote);
            }
        }

        $redirect = $this->redirectFactory->create();
        $redirect->setPath('checkout/cart');
        return $redirect;
    }
}
