<?php

namespace Iyzico\Iyzipay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;

class CronHelper extends AbstractHelper
{
    const XML_PATH_COMMON_CRON_SETTINGS = 'payment/iyzipay/common_cron_settings';
    const XML_PATH_CUSTOM_CRON_SETTINGS = 'payment/iyzipay/custom_cron_settings';
    const XML_PATH_EFFECTIVE_CRON_SCHEDULE = 'crontab/default/jobs/iyzico_process_pending_orders/schedule/cron_expr';

    protected $configWriter;
    protected $scopeConfig;
    protected $websiteId;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->websiteId = $storeManager->getWebsite()->getId();
    }

    public function getCronSchedule()
    {
        $commonSettings = $this->scopeConfig->getValue(
            self::XML_PATH_COMMON_CRON_SETTINGS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->websiteId
        );

        if ($commonSettings && $commonSettings !== 'custom') {
            $this->saveEffectiveCronSchedule($commonSettings);
            return $commonSettings;
        }

        $customCronSettings = $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_CRON_SETTINGS,
            ScopeInterface::SCOPE_WEBSITES,
            $this->websiteId
        );

        if ($customCronSettings) {
            $this->saveEffectiveCronSchedule($customCronSettings);
            return $customCronSettings;
        }

        return '0 0 * * *';
    }

    private function saveEffectiveCronSchedule($schedule)
    {
        $this->configWriter->save(
            self::XML_PATH_EFFECTIVE_CRON_SCHEDULE,
            $schedule,
            ScopeInterface::SCOPE_WEBSITES,
            $this->websiteId
        );
        $this->configWriter->save(
            self::XML_PATH_EFFECTIVE_CRON_SCHEDULE,
            $schedule,
            ScopeInterface::SCOPE_WEBSITES,
            $this->websiteId
        );
    }
}
