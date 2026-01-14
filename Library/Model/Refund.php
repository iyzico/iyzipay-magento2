<?php

namespace Iyzico\Iyzipay\Library\Model;

use Iyzico\Iyzipay\Library\Model\Mapper\RefundMapper;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\CreateRefundRequest;

class Refund extends RefundResource
{
    public static function create(CreateRefundRequest $request, Options $options)
    {
        $url = "/payment/refund";
        $rawResult = parent::httpClient()->post($options->getBaseUrl() . $url, parent::getHttpHeadersV2($url, $request, $options), $request->toJsonString());
        return RefundMapper::create($rawResult)->jsonDecode()->mapRefund(new Refund());
    }
}