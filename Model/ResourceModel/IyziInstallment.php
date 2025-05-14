<?php

namespace Iyzico\Iyzipay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IyziInstallment extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_installment_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('iyzico_installment', 'installment_id');
        $this->_useIsObjectNew = true;
    }
}
