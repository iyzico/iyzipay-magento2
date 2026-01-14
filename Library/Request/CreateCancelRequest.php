<?php

namespace Iyzico\Iyzipay\Library\Request;

use Iyzico\Iyzipay\Library\JsonBuilder;
use Iyzico\Iyzipay\Library\Request;
use Iyzico\Iyzipay\Library\RequestStringBuilder;

class CreateCancelRequest extends Request
{
    private $paymentId;
    private $ip;
    private $reason;
    private $description;

    public function getJsonObject()
    {
        return JsonBuilder::fromJsonObject(parent::getJsonObject())
            ->add("paymentId", $this->getPaymentId())
            ->add("ip", $this->getIp())
            ->add("reason", $this->getReason())
            ->add("description", $this->getDescription())
            ->getObject();
    }

    public function getPaymentId()
    {
        return $this->paymentId;
    }

    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function toPKIRequestString()
    {
        return RequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->append("paymentId", $this->getPaymentId())
            ->append("ip", $this->getIp())
            ->append("reason", $this->getReason())
            ->append("description", $this->getDescription())
            ->getRequestString();
    }
}