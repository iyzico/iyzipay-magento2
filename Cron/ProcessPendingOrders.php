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

namespace Iyzico\Iyzipay\Cron;

use Exception;
use Iyzico\Iyzipay\Helper\ProcessPendingOrderHelper;
use Iyzico\Iyzipay\Logger\IyziCronLogger;

class ProcessPendingOrders
{

    public function __construct(
        protected readonly IyziCronLogger $cronLogger,
        protected readonly ProcessPendingOrderHelper $processPendingOrderHelper
    ) {
    }

    public function execute()
    {
        try {
            $this->cronLogger->info('iyzico cron job started');

            $page = 1;
            $processedCount = 0;
            $totalPages = $this->processPendingOrderHelper->getTotalPages();

            $ordersToDelete = $this->processPendingOrderHelper->getOrdersToDelete($page);
            if (!empty($ordersToDelete)) {
                $this->processPendingOrderHelper->deleteProcessedOrders($ordersToDelete);
            }

            while ($page <= $totalPages) {
                $orders = $this->processPendingOrderHelper->getPageOfOrders($page);
                $ordersCount = count($orders);

                if ($ordersCount > 0) {
                    $this->processPendingOrderHelper->processOrders($orders);
                    $processedCount += $ordersCount;
                }

                $this->cronLogger->info("Processed batch", [
                    'page' => $page,
                    'processed_count' => $ordersCount,
                    'total_processed' => $processedCount
                ]);

                $page++;
            }

            $this->cronLogger->info('iyzico cron job completed', ['total_processed' => $processedCount]);

            return ['success' => true, 'message' => "Processed $processedCount orders"];
        } catch (Exception $e) {
            $this->cronLogger->error('iyzico cron job failed: '.$e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

}
