<?php

namespace Iyzico\Iyzipay\Library\Model\Mapper;

use Iyzico\Iyzipay\Library\Model\Refund;

class RefundMapper extends RefundResourceMapper
{
    public static function create($rawResult = null)
    {
        return new RefundMapper($rawResult);
    }

    public function mapRefund(Refund $refund)
    {
        return $this->mapRefundFrom($refund, $this->jsonObject);
    }

    public function mapRefundFrom(Refund $refund, $jsonObject)
    {
        parent::mapRefundResourceFrom($refund, $jsonObject);
        return $refund;
    }
}