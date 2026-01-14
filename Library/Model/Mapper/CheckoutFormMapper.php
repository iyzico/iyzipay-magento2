<?php

namespace Iyzico\Iyzipay\Library\Model\Mapper;
use Iyzico\Iyzipay\Library\Model\CheckoutForm;

class CheckoutFormMapper extends PaymentResourceMapper
{
    public static function create($rawResult = null)
    {
        return new CheckoutFormMapper($rawResult);
    }

    public function mapCheckoutFormFrom(CheckoutForm $auth, $jsonObject)
    {
        parent::mapPaymentResourceFrom($auth, $jsonObject);

        if (isset($jsonObject->token)) {
            $auth->setToken($jsonObject->token);
        }
        if (isset($jsonObject->callbackUrl)) {
            $auth->setCallbackUrl($jsonObject->callbackUrl);
        }
        return $auth;
    }

    public function mapCheckoutForm(CheckoutForm $auth)
    {
        return $this->mapCheckoutFormFrom($auth, $this->jsonObject);
    }
}
