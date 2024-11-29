<?php

namespace Iyzico\Iyzipay\Library\Model\Mapper;

use Iyzico\Iyzipay\Library\Model\CheckoutFormInitialize;

class CheckoutFormInitializeMapper extends CheckoutFormInitializeResourceMapper
{
    public static function create($rawResult = null)
    {
        return new CheckoutFormInitializeMapper($rawResult);
    }

    public function mapCheckoutFormInitialize(CheckoutFormInitialize $initialize)
    {
        return $this->mapCheckoutFormInitializeFrom($initialize, $this->jsonObject);
    }

    public function mapCheckoutFormInitializeFrom(CheckoutFormInitialize $initialize, $jsonObject)
    {
        parent::mapCheckoutFormInitializeResourceFrom($initialize, $jsonObject);
        return $initialize;
    }
}