<?php

namespace Iyzico\Iyzipay\Library\Model;

use Iyzico\Iyzipay\Library\Model\Mapper\CheckoutFormInitializeMapper;
use Iyzico\Iyzipay\Library\Request\CreateCheckoutFormInitializeRequest;
use Iyzico\Iyzipay\Library\Options;

class CheckoutFormInitialize extends CheckoutFormInitializeResource
{
    public static function create(CreateCheckoutFormInitializeRequest $request, Options $options)
    {
        $uri = "/payment/iyzipos/checkoutform/initialize/auth/ecom";
        $rawResult = parent::httpClient()->post($options->getBaseUrl() . $uri, parent::getHttpHeadersV2($uri, $request, $options), $request->toJsonString());
        return CheckoutFormInitializeMapper::create($rawResult)->jsonDecode()->mapCheckoutFormInitialize(new CheckoutFormInitialize());
    }
}
