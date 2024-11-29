<?php

namespace Iyzico\Iyzipay\Library;

abstract class BaseModel implements JsonConvertible, RequestStringConvertible
{
    public function toJsonString()
    {
        return JsonBuilder::jsonEncode($this->getJsonObject());
    }
}