<?php

namespace Iyzico\Iyzipay\Service;

use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Library\Model\CheckoutForm;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Model\IyziCardFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziCard as IyziCardResource;
use Iyzico\Iyzipay\Model\ResourceModel\IyziCard\CollectionFactory as IyziCardCollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Throwable;

readonly class CardService
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
     * @param  string  $apiKey
     * @param  int|null  $customerId
     * @return void
     * @throws AlreadyExistsException
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
