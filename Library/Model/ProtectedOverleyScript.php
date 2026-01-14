<?php

namespace Iyzico\Iyzipay\Library\Model;

use Iyzico\Iyzipay\Library\IyzipayResource;
use Iyzico\Iyzipay\Library\Model\Mapper\ProtectedOverleyScriptMapper;
use Iyzico\Iyzipay\Library\Options;
use Iyzico\Iyzipay\Library\Request\RetrieveProtectedOverleyScriptRequest;

class ProtectedOverleyScript extends IyzipayResource
{
    private $protectedShopId;
    private $overlayScript;

    public static function retrieve(RetrieveProtectedOverleyScriptRequest $request, Options $options)
    {
        $url = "/v1/iyziup/protected/shop/detail/overlay-script";
        $rawResult = parent::httpClient()->post($options->getBaseUrl().$url,
            parent::getHttpHeadersV2($url, $request, $options), $request->toJsonString());
        return ProtectedOverleyScriptMapper::create($rawResult)->jsonDecode()->mapProtectedOverleyScript(new ProtectedOverleyScript());
    }

    public function getProtectedShopId()
    {
        return $this->protectedShopId;
    }

    public function setProtectedShopId($protectedShopId)
    {
        $this->protectedShopId = $protectedShopId;
    }

    public function getOverlayScript()
    {
        return $this->overlayScript;
    }

    public function setOverlayScript($overlayScript)
    {
        $this->overlayScript = $overlayScript;
    }
}