<?php

namespace Iyzico\Iyzipay\Model\Postbackwebhook;

/**
 * JSON decoder
 *
 * @api
 */
interface DecoderInterface
{
    /**
     * Decodes the given $data string which is encoded in the x-www-form-urlencoded format into a PHP type (array, string literal, etc.)
     *
     * @param  string $data
     * @return mixed
     */
    public function decode($data);
}
