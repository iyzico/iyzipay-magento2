<?php

namespace Iyzico\Iyzipay\Logger\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class IyziWebhookHandler extends StreamHandler
{
    public function __construct()
    {
        $filePath = BP . '/var/log/iyzipay_webhook.log';
        parent::__construct($filePath, Logger::DEBUG);
    }
}
