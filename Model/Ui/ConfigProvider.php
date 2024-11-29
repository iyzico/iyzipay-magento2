<?php

namespace Iyzico\Iyzipay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

abstract class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'iyzipay';
}