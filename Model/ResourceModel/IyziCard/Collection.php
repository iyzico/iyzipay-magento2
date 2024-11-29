<?php

namespace Iyzico\Iyzipay\Model\ResourceModel\IyziCard;

use Iyzico\Iyzipay\Model\IyziCard as Model;
use Iyzico\Iyzipay\Model\ResourceModel\IyziCard as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_card_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
