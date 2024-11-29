<?php
namespace Iyzico\Iyzipay\Logger;

use Monolog\Logger;
use Iyzico\Iyzipay\Logger\Handler\IyziErrorHandler;

class IyziErrorLogger extends Logger
{
    public function __construct(IyziErrorHandler $handler, array $processors = [])
    {
        parent::__construct('iyzipay_error', [$handler], $processors);
    }
}


