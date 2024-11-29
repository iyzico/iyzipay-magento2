<?php

namespace Iyzico\Iyzipay\Model;

use Iyzico\Iyzipay\Model\ResourceModel\IyziOrderJob as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class IyziOrderJob extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'iyzico_order_job_model';

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
