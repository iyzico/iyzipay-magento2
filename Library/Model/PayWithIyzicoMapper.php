<?php

namespace Iyzico\Iyzipay\Library\Model;

use Iyzico\Iyzipay\Library\Model\Mapper\PaymentResourceMapper;

class PayWithIyzicoMapper extends PaymentResourceMapper
{
    public static function create($rawResult = null)
    {
        return new PayWithIyzicoMapper($rawResult);
    }

    public function mapPayWithIyzico(PayWithIyzico $auth)
    {
        return $this->mapPayWithIyzicoFrom($auth, $this->jsonObject);
    }

    public function mapPayWithIyzicoFrom(PayWithIyzico $auth, $jsonObject)
    {
        parent::mapPaymentResourceFrom($auth, $jsonObject);

        if (isset($jsonObject->token)) {
            $auth->setToken($jsonObject->token);
        }
        if (isset($jsonObject->callbackUrl)) {
            $auth->setCallbackUrl($jsonObject->callbackUrl);
        }
        if (isset($jsonObject->paymentStatus)) {
            $auth->setPaymentStatus($jsonObject->paymentStatus);
        }
        if (isset($jsonObject->signature)) {
            $auth->setSignature($jsonObject->signature);
        }
        return $auth;
    }
}