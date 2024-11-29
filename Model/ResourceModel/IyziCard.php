<?php

namespace Iyzico\Iyzipay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IyziCard extends AbstractDb
{
    /**
     * @var string
     */
    protected string $_eventPrefix = 'iyzico_card_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct(): void
    {
        $this->_init('iyzico_card', 'iyzico_card_id');
        $this->_useIsObjectNew = true;
    }
}
