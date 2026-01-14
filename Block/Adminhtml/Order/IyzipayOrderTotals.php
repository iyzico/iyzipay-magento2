<?php

namespace Iyzico\Iyzipay\Block\Adminhtml\Order;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Adminhtml\Order\Totals as AdminOrderTotals;
use Magento\Sales\Helper\Admin;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class IyzipayOrderTotals
 */
class IyzipayOrderTotals extends AdminOrderTotals
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * IyzipayOrderTotals constructor
     *
     * @param  Context  $context
     * @param  Registry  $registry
     * @param  Admin  $adminHelper
     * @param  RequestInterface  $request
     * @param  OrderRepositoryInterface  $orderRepository
     * @param  array  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        // Pass all required parameters to parent constructor
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * Format total value based on order currency
     *
     * @param  DataObject  $total
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
        // Call parent to get base totals (subtotal, discount, shipping, grand_total)
        parent::_initTotals();

        /**
         * Add Installment Fee after subtotal and before grand_total
         */
        $installmentFee = (double) $this->getSource()->getInstallmentFee();
        if ($installmentFee != 0) {
            // Store grand_total temporarily
            $grandTotal = $this->_totals['grand_total'] ?? null;

            // Remove grand_total from array temporarily
            if ($grandTotal) {
                unset($this->_totals['grand_total']);
            }

            // Add installment fee
            $installmentCount = $this->getSource()->getInstallmentCount() ?? 1;
            $this->_totals['installment_fee'] = new DataObject(
                [
                    'code' => 'installment_fee',
                    'value' => $installmentFee,
                    'base_value' => $installmentFee,
                    'label' => $installmentCount.' '.__('Installment'),
                ]
            );

            // Update grand_total to include installment fee
            if ($grandTotal) {
                $originalGrandTotal = $grandTotal->getValue();
                $originalBaseGrandTotal = $grandTotal->getBaseValue();

                $this->_totals['grand_total'] = new DataObject(
                    [
                        'code' => 'grand_total',
                        'strong' => true,
                        'value' => $originalGrandTotal + $installmentFee,
                        'base_value' => $originalBaseGrandTotal + $installmentFee,
                        'label' => __('Grand Total'),
                        'area' => 'footer',
                    ]
                );
            }
        }

        return $this;
    }
}
