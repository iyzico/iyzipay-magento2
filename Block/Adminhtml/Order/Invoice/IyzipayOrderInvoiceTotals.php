<?php

/**
 * iyzico Payment Gateway For Magento 2
 * Copyright (C) 2018 iyzico
 *
 * This file is part of Iyzico/Iyzipay.
 *
 * Iyzico/Iyzipay is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Iyzico\Iyzipay\Block\Adminhtml\Order\Invoice;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Totals;
use Magento\Sales\Helper\Admin;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

/**
 * Class IyzipayOrderInvoiceTotals
 *
 * This class extends Totals and is used to add custom totals to the invoice view in the admin panel.
 *
 * @package Iyzico\Iyzipay\Block\Adminhtml\Order\Invoice
 * @extends Totals
 *
 * This class is used etc/di.xml
 */
class IyzipayOrderInvoiceTotals extends Totals
{

    /**
     * Admin Helper
     *
     * @var Admin
     */
    protected Admin $_adminHelper;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected InvoiceRepositoryInterface $invoiceRepository;

    /**
     * IyzipayOrderInvoiceTotals constructor
     *
     * @param  Context  $context
     * @param  Registry  $registry
     * @param  Admin  $adminHelper
     * @param  RequestInterface  $request
     * @param  InvoiceRepositoryInterface  $invoiceRepository
     * @param  array  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        RequestInterface $request,
        InvoiceRepositoryInterface $invoiceRepository,
        array $data = []
    ) {
        $this->_adminHelper = $adminHelper;
        $this->request = $request;
        $this->invoiceRepository = $invoiceRepository;
        parent::__construct($context, $registry, $data);
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
