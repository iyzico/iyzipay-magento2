<?php

namespace Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob;

use Iyzico\Iyzipay\Model\IyziOrderJob as Model;
use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_order_job_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
