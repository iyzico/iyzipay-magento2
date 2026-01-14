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

use Iyzico\Iyzipay\Helper\CronHelper;
use Iyzico\Iyzipay\Logger\IyziCronLogger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class UpdateCronSchedule implements ObserverInterface
{
    public function __construct(
        protected CronHelper $cronHelper,
        protected IyziCronLogger $logger
    ) {
    }

    /**
     * Execute observer
     *
     * @param  Observer  $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        try {
            $newSchedule = $this->cronHelper->getCronSchedule();
            $this->logger->info('Cron schedule updated: '.$newSchedule);
        } catch (LocalizedException $e) {
            $this->logger->error('Cron schedule update failed: '.$e->getMessage());
        }
    }
}
