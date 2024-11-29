<?php
namespace Iyzico\Iyzipay\Logger;

use Monolog\Logger;
use Iyzico\Iyzipay\Logger\Handler\IyziCronHandler;

class IyziCronLogger extends Logger
{
    public function __construct(IyziCronHandler $handler, array $processors = [])
    {
        parent::__construct('iyzipay_cron', [$handler], $processors);
    }
}
