<?php

/**
 * iyzico Payment Gateway For Magento 2
 * Copyright (C) 2018 iyzico
 *
 * This file is part of Iyzico/Iyzipay.
 *
 * Iyzico/Iyzipay is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Iyzico\Iyzipay\Helper;

use Magento\Framework\Exception\LocalizedException;

class CronHelper
{
    public function __construct(
        protected ConfigHelper $configHelper
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function getCronSchedule(): string
    {
        $commonSettings = $this->configHelper->getCommonCronSettings();
        if ($commonSettings && $commonSettings !== 'custom') {
            $this->configHelper->setCronSettings($commonSettings);
            return $commonSettings;
        }

        $customCronSettings = $this->configHelper->getCustomCronSettings();
        if (!empty($customCronSettings)) {
            $this->configHelper->setCronSettings($customCronSettings);
            return $customCronSettings;
        }

        return '0 0 * * *';
    }
}
