<?php

namespace Iyzico\Iyzipay\Library\Model;

use Iyzico\Iyzipay\Library\Model\Mapper\CheckoutFormMapper;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\RetrieveCheckoutFormRequest;

class CheckoutForm extends PaymentResource
{
    private $token;
    private $callbackUrl;
    private $signature;

    public static function retrieve(RetrieveCheckoutFormRequest $request, Options $options)
    {
        $token = $request->getToken();
        $uri = "/payment/iyzipos/checkoutform/auth/ecom/detail/";
        $rawResult = parent::httpClient()->post($options->getBaseUrl() . $uri, parent::getHttpHeadersV2($uri, $request, $options), $request->toJsonString());
        return CheckoutFormMapper::create($rawResult)->jsonDecode()->mapCheckoutForm(new CheckoutForm());
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getSignature()
    {
        return $this->signature;
    }

    public function setSignature($signature)
    {
        return $this->signature = $signature;
    }
}