<?php

namespace Iyzico\Iyzipay\Model;

use Iyzico\Iyzipay\Model\ResourceModel\IyziCard as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class IyziCard extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_card_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
