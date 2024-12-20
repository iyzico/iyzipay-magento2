<?php

namespace Iyzico\Iyzipay\Controller\Redirect;

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
        protected readonly RedirectFactory $redirectFactory,
        protected readonly QuoteRepository $quoteRepository,
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly RequestInterface $request,
        protected readonly OrderService $orderService,
        protected readonly OrderJobService $orderJobService
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
        $quoteId = $this->request->getParam('quote_id');
        if ($quoteId) {
            $quote = $this->quoteRepository->get($quoteId);

            $orderId = $quote->getReservedOrderId();
            if ($orderId) {
                $order = $this->orderRepository->get($orderId);
                $order->setState('canceled')->setStatus('canceled');

                $this->orderService->releaseStock($order);
                $this->orderJobService->removeIyziOrderJobTable($orderId);

                $this->orderRepository->save($order);
            }

            $quote->setIsActive(1);
            $this->quoteRepository->save($quote);
        }

        $redirect = $this->redirectFactory->create();
        $redirect->setPath('checkout/cart');
        return $redirect;
    }
}
