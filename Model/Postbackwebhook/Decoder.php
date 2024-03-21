<?php

namespace Iyzico\Iyzipay\Model\Postbackwebhook;

use Magento\Framework;

/**
 * Class Decoder
 *
 * @package Iyzico\Iyzipay\Model\Postbackwebhook
 */
class Decoder implements DecoderInterface
{
    /**
     * @param  string $data
     * @return mixed
     */
    public function decode($data)
    {
        parse_str($data, $result);
        return $result;
    }
}
