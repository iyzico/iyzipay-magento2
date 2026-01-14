<?php

namespace Iyzico\Iyzipay\Library\Model\Mapper;

use Iyzico\Iyzipay\Library\Model\ProtectedOverleyScript;

class ProtectedOverleyScriptMapper extends IyzipayResourceMapper
{
    public static function create($rawResult = null)
    {
        return new ProtectedOverleyScriptMapper($rawResult);
    }

    public function mapProtectedOverleyScript(ProtectedOverleyScript $protectedOverleyScript)
    {
        return $this->mapProtectedOverleyScriptFrom($protectedOverleyScript, $this->jsonObject);
    }

    public function mapProtectedOverleyScriptFrom(ProtectedOverleyScript $protectedOverleyScript, $jsonObject)
    {
        parent::mapResourceFrom($protectedOverleyScript, $jsonObject);

        if (isset($jsonObject->protectedShopId)) {
            $protectedOverleyScript->setProtectedShopId($jsonObject->protectedShopId);
        }
        if (isset($jsonObject->overlayScript)) {
            $protectedOverleyScript->setOverlayScript($jsonObject->overlayScript);
        }
        return $protectedOverleyScript;
    }
}