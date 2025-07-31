<?php

namespace Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment;

use Iyzico\Iyzipay\Model\IyziInstallment;
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_installment_collection';

    /**
     * @var string
     */
    protected $_idFieldName = 'installment_id';

    /**
     * Initialize collection model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IyziInstallment::class, ResourceModel::class);
    }
}
