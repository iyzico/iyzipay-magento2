<?php

namespace Iyzico\Iyzipay\Helper;

use Magento\Framework\Exception\LocalizedException;

class CronHelper
{
    public function __construct(
        protected readonly ConfigHelper $configHelper
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function getCronSchedule()
    {
        $commonSettings = $this->configHelper->getCommonCronSettings();
        if ($commonSettings && $commonSettings !== 'custom') {
            $this->configHelper->setCronSettings($commonSettings);
            return $commonSettings;
        }

        $customCronSettings = $this->configHelper->getCustomCronSettings();
        if ($customCronSettings) {
            $this->configHelper->setCronSettings($customCronSettings);
            return $customCronSettings;
        }

        return '0 0 * * *';
    }
}
