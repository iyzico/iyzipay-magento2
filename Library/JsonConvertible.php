<?php

namespace Iyzico\Iyzipay\Library;

interface JsonConvertible
{
    public function getJsonObject();

    public function toJsonString();
}