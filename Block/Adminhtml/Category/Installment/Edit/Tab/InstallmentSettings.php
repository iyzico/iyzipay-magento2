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

namespace Iyzico\Iyzipay\Block\Adminhtml\Category\Installment\Edit\Tab;

use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment as IyziInstallmentResource;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class InstallmentSettings extends Template
{
    /**
     * @var IyziInstallmentFactory
     */
    protected $iyziInstallmentFactory;

    /**
     * @var IyziInstallmentResource
     */
    protected $iyziInstallmentResource;

    /**
     * @var \Iyzico\Iyzipay\Model\IyziInstallment|null
     */
    protected $installmentModel = null;

    /**
     * @var string
     */
    protected $_template = 'Iyzico_Iyzipay::category/installment/edit/tab/installment-settings.phtml';

    /**
     * @param Context $context
     * @param IyziInstallmentFactory $iyziInstallmentFactory
     * @param IyziInstallmentResource $iyziInstallmentResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        IyziInstallmentFactory $iyziInstallmentFactory,
        IyziInstallmentResource $iyziInstallmentResource,
        array $data = []
    ) {
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->iyziInstallmentResource = $iyziInstallmentResource;
        parent::__construct($context, $data);
    }

    /**
     * Get current installment model
     *
     * @return \Iyzico\Iyzipay\Model\IyziInstallment
     */
    public function getInstallment()
    {
        if ($this->installmentModel === null) {
            $id = $this->getRequest()->getParam('id');
            $model = $this->iyziInstallmentFactory->create();

            if ($id) {
                $this->iyziInstallmentResource->load($model, $id, 'category_id');
            }

            $this->installmentModel = $model;
        }

        return $this->installmentModel;
    }

    /**
     * Get installment settings
     *
     * @return array
     */
    public function getInstallmentSettings()
    {
        $installment = $this->getInstallment();
        $settings = [];

        if ($installment && $installment->getSettings()) {
            try {
                $settingsData = $installment->getSettings();
                $settings = json_decode($settingsData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return [];
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * Get available installment options
     *
     * @return array
     */
    public function getAvailableInstallments()
    {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    }

    /**
     * Get selected installment numbers from settings
     *
     * @return array
     */
    public function getSelectedInstallmentNumbers()
    {
        $settings = $this->getInstallmentSettings();

        // Eğer ayarlar direkt dizi olarak geldiyse
        if (is_array($settings) && isset($settings[0]) && !is_array($settings[0])) {
            return $settings;
        }

        return [];
    }

    /**
     * Get percentage value for a specific installment option
     *
     * @param int $installmentNumber
     * @return string
     */
    public function getPercentageValue($installmentNumber)
    {
        $settings = $this->getInstallmentSettings();

        if (is_array($settings)) {
            foreach ($settings as $setting) {
                if (isset($setting['installment']) && isset($setting['percentage']) &&
                    (int)$setting['installment'] === (int)$installmentNumber) {
                    return $setting['percentage'];
                }
            }
        }

        return '0.00';
    }
}
