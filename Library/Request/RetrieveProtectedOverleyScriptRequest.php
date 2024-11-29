<?php

namespace Iyzico\Iyzipay\Library\Request;

use Iyzico\Iyzipay\Library\JsonBuilder;
use Iyzico\Iyzipay\Library\Request;
use Iyzico\Iyzipay\Library\RequestStringBuilder;

class RetrieveProtectedOverleyScriptRequest extends Request
{
    private $position;

    public function getJsonObject()
    {
        return JsonBuilder::fromJsonObject(parent::getJsonObject())
            ->add("position", $this->getPosition())
            ->getObject();
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function toPKIRequestString()
    {
        return RequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->append("position", $this->getPosition())
            ->getRequestString();
    }
}