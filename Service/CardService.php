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

namespace Iyzico\Iyzipay\Service;

use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Library\Model\CheckoutForm;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Model\IyziCardFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziCard as IyziCardResource;
use Iyzico\Iyzipay\Model\ResourceModel\IyziCard\CollectionFactory as IyziCardCollectionFactory;
use Throwable;

class CardService
{
    public function __construct(
        protected IyziCardFactory $iyziCardFactory,
        protected IyziCardResource $iyziCardResource,
        protected IyziCardCollectionFactory $iyziCardCollectionFactory,
        protected IyziErrorLogger $errorLogger,
        protected ConfigHelper $configHelper,
    ) {
    }

    /**
     * Save User Card
     *
     * This function is responsible for saving the user card.
     *
     * @param  CheckoutForm  $response
     * @param  int|null  $customerId
     * @return void
     */
    public function setUserCard(CheckoutForm $response, int|null $customerId): void
    {
        if ($response->getCardUserKey() !== null && $customerId != 0) {
            try {
                $apiKey = $this->configHelper->getApiKey();
                $collection = $this->iyziCardCollectionFactory->create()
                    ->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('api_key', $apiKey)
                    ->addFieldToSelect('card_user_key');

                $iyziCard = $collection->getFirstItem();
                $customerCardUserKey = $iyziCard->getData('card_user_key');

                if ($response->getCardUserKey() != $customerCardUserKey) {
                    if ($iyziCard->getIyzicoCardId()) {
                        $iyziCard->setCardUserKey($response->getCardUserKey());
                    } else {
                        $iyziCard = $this->iyziCardFactory->create();
                        $iyziCard->setData([
                            'customer_id' => $customerId,
                            'card_user_key' => $response->getCardUserKey(),
                            'api_key' => $apiKey,
                        ]);
                    }
                    $this->iyziCardResource->save($iyziCard);
                }
            } catch (Throwable $th) {
                $this->errorLogger->critical("setUserCard: ".$th->getMessage(), [
                    'fileName' => __FILE__,
                    'lineNumber' => __LINE__,
                ]);
            }
        }
    }
}
