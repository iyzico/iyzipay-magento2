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

namespace Iyzico\Iyzipay\Block\Adminhtml\Product\Installment\Edit;

use Iyzico\Iyzipay\Api\Data\IyziInstallmentInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var DataPersistorInterface
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @param  Context  $context
     * @param  DataPersistorInterface  $dataPersistor
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor
    ) {
        $this->context = $context;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getModelId()) {
            $data = [
                'label' => __('Sil'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''.__(
                    'Silmek istediğinize emin misiniz?'
                ).'\', \''.$this->getDeleteUrl().'\')',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Return model ID
     *
     * @return int|null
     */
    public function getModelId(): ?int
    {
        $installment = $this->getInstallment();
        return $installment ? $installment->getId() : null;
    }

    /**
     * Get installment model
     *
     * @return IyziInstallmentInterface|null
     */
    protected function getInstallment(): ?IyziInstallmentInterface
    {
        return $this->dataPersistor->get('iyzico_installment');
    }

    /**
     * Get URL for delete button
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->context->getUrlBuilder()->getUrl('*/*/delete', ['id' => $this->getModelId()]);
    }
}
