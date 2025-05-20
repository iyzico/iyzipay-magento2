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

namespace Iyzico\Iyzipay\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Iyzico\Iyzipay\Cron\ProcessPendingOrders;

class ProcessIyzicoOrders implements HttpGetActionInterface
{
    public function __construct(
        protected JsonFactory $jsonFactory,
        protected ProcessPendingOrders $processPendingOrders
    ) {
    }

    public function execute()
    {
        try {
            $result = $this->processPendingOrders->execute();

            $resultJson = $this->jsonFactory->create();
            return $resultJson->setData($result);
        } catch (\Exception $e) {
            $resultJson = $this->jsonFactory->create();
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
