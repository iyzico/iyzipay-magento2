<?php

namespace Iyzico\Iyzipay\Library\Request;

use Iyzico\Iyzipay\Library\JsonBuilder;
use Iyzico\Iyzipay\Library\Request;
use Iyzico\Iyzipay\Library\RequestStringBuilder;

class RetrieveCheckoutFormRequest extends Request
{
    private $token;

    public function getJsonObject()
    {
        return JsonBuilder::fromJsonObject(parent::getJsonObject())
            ->add("token", $this->getToken())
            ->getObject();
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function toPKIRequestString()
    {
        return RequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->append("token", $this->getToken())
            ->getRequestString();
    }
}