<?php

namespace Iyzico\Iyzipay\Plugin\Magento\Framework\Webapi\Rest;

/**
 * Class Request
 *
 * @package Iyzico\Iyzipay\Plugin\Magento\Framework\Webapi\Rest
 */
class Request
{

    /**
     * @param  \Magento\Framework\Webapi\Rest\Request $subject
     * @param  array                                  $result
     * @return array|string[]
     */
    public function afterGetAcceptTypes(\Magento\Framework\Webapi\Rest\Request $subject, array $result): array
    {
        if ($subject->getRequestUri() === '/rest/V1/iyzico/webhook/' || $subject->getRequestUri() === '/index.php/rest/V1/iyzico/callback/') {
            $result = ['text/html'];
        }
        return $result;
    }
}
