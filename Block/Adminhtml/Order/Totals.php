<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Iyzico\PayWithIyzico\Block\Adminhtml\Order;

class Totals extends \Magento\Sales\Block\Order\Totals
{
    /**
     * Admin helper
     *
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = []
    ) {
        $this->_adminHelper = $adminHelper;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Format total value based on order currency
     *
     * @param \Magento\Framework\DataObject $total
     * @return string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->_adminHelper->displayPrices($this->getOrder(), $total->getBaseValue(), $total->getValue());
        }
        return $total->getValue();
    }

    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        $this->_totals = [];

        /**
         * Add Installment Fee
         */
        if ((double)$this->getSource()->getInstallmentFee() != 0) {
            $this->_totals['installment_fee'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'installment_fee',
                    'value' => $this->getSource()->getInstallmentFee(),
                    'base_value' => $this->getSource()->getInstallmentFee(),
                    'label' => $this->getSource()->getInstallmentCount().' '.__('Installment'),
                ]
            );
        }

        $this->_totals['subtotal'] = new \Magento\Framework\DataObject(
            [
                'code' => 'subtotal',
                'value' => $this->getSource()->getSubtotal(),
                'base_value' => $this->getSource()->getBaseSubtotal(),
                'label' => __('Subtotal'),
            ]
        );

        /**
         * Add shipping
         */
        if (!$this->getSource()->getIsVirtual() && ((double)$this->getSource()->getShippingAmount() ||
            $this->getSource()->getShippingDescription())
        ) {
            $this->_totals['shipping'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping',
                    'value' => $this->getSource()->getShippingAmount(),
                    'base_value' => $this->getSource()->getBaseShippingAmount(),
                    'label' => __('Shipping & Handling'),
                ]
            );
        }

        /**
         * Add discount
         */
        if ((double)$this->getSource()->getDiscountAmount() != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = __('Discount (%1)', $this->getSource()->getDiscountDescription());
            } else {
                $discountLabel = __('Discount');
            }
            $this->_totals['discount'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'discount',
                    'value' => $this->getSource()->getDiscountAmount(),
                    'base_value' => $this->getSource()->getBaseDiscountAmount(),
                    'label' => $discountLabel,
                ]
            );
        }

        $this->_totals['grand_total'] = new \Magento\Framework\DataObject(
            [
                'code' => 'grand_total',
                'strong' => true,
                'value' => $this->getSource()->getGrandTotal(),
                'base_value' => $this->getSource()->getBaseGrandTotal(),
                'label' => __('Grand Total'),
                'area' => 'footer',
            ]
        );

                $this->_totals['paid'] = new \Magento\Framework\DataObject(
            [
                'code' => 'paid',
                'strong' => true,
                'value' => $this->getSource()->getTotalPaid(),
                'base_value' => $this->getSource()->getBaseTotalPaid(),
                'label' => __('Total Paid'),
                'area' => 'footer',
            ]
        );
        $this->_totals['refunded'] = new \Magento\Framework\DataObject(
            [
                'code' => 'refunded',
                'strong' => true,
                'value' => $this->getSource()->getTotalRefunded(),
                'base_value' => $this->getSource()->getBaseTotalRefunded(),
                'label' => __('Total Refunded'),
                'area' => 'footer',
            ]
        );
        $this->_totals['due'] = new \Magento\Framework\DataObject(
            [
                'code' => 'due',
                'strong' => true,
                'value' => $this->getSource()->getTotalDue(),
                'base_value' => $this->getSource()->getBaseTotalDue(),
                'label' => __('Total Due'),
                'area' => 'footer',
            ]
        );

        return $this;
    }
}
