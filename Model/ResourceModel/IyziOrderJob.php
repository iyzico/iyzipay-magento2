<?php

namespace Iyzico\Iyzipay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IyziOrderJob extends AbstractDb
{
    /**
     * @var string
     */
    protected string $_eventPrefix = 'iyzico_order_job_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct(): void
    {
        $this->_init('iyzico_order_job', 'id');
        $this->_useIsObjectNew = true;
    }
}
