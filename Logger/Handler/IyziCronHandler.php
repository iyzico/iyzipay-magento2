<?php
namespace Iyzico\Iyzipay\Logger\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class IyziCronHandler extends StreamHandler
{
    public function __construct()
    {
        $filePath = BP . '/var/log/iyzipay_cron.log';
        parent::__construct($filePath, Logger::DEBUG);
    }
}
