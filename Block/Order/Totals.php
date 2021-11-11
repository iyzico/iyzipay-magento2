<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Iyzico\Iyzipay\Block\Order;

use Magento\Sales\Model\Order;

/**
 * @api
 * @since 100.0.2
 */
class Totals extends \Magento\Sales\Block\Order\Totals
{
    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        parent::_initTotals();

        $source = $this->getSource();
        if ((double)$this->getSource()->getInstallmentFee() != 0) {
            $this->_totals['installment_fee'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'installment_fee',
                    'field' => 'installment_fee',
                    'strong' => true,
                    'value' => $source->getInstallmentFee(),
                    'label' => $source->getInstallmentCount().' '.__('Installment'),
                ]
            );
        }

        return $this;
    }
}
