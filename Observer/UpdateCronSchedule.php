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

namespace Iyzico\Iyzipay\Observer;

use Iyzico\Iyzipay\Logger\IyziCronLogger;
use Magento\Framework\Event\ObserverInterface;
use Iyzico\Iyzipay\Helper\CronHelper;
use Magento\Framework\Event\Observer;

class UpdateCronSchedule implements ObserverInterface
{
    private $cronHelper;
    private $logger;

    public function __construct(CronHelper $cronHelper, IyziCronLogger $logger)
    {
        $this->cronHelper = $cronHelper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $newSchedule = $this->cronHelper->getCronSchedule();
        $this->logger->info('Cron schedule updated: ' . $newSchedule);
    }
}
