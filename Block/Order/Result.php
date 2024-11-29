<?php

namespace Iyzico\Iyzipay\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Result extends Template
{
    protected $_request;
    protected $_assetRepository;
    protected $priceCurrency;

    public function __construct(
        Template\Context $context,
        RequestInterface $request,
        Repository $assetRepository,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->_request = $request;
        $this->_assetRepository = $assetRepository;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    public function formatPrice($price)
    {
        return $this->priceCurrency->format(
            $price,
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getData('order') ? $this->getData('order')->getStore() : null
        );
    }
}
