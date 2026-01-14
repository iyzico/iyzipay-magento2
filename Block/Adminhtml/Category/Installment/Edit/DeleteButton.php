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

namespace Iyzico\Iyzipay\Block\Adminhtml\Category\Installment\Edit;

use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment as IyziInstallmentResource;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

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
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param IyziInstallmentFactory $iyziInstallmentFactory
     * @param IyziInstallmentResource $iyziInstallmentResource
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        IyziInstallmentFactory $iyziInstallmentFactory,
        IyziInstallmentResource $iyziInstallmentResource
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->iyziInstallmentResource = $iyziInstallmentResource;
    }

    /**
     * Get installment model
     *
     * @return \Iyzico\Iyzipay\Model\IyziInstallment|null
     */
    protected function getInstallment()
    {
        if ($this->installmentModel === null) {
            $id = $this->request->getParam('id');
            if ($id) {
                $model = $this->iyziInstallmentFactory->create();
                $this->iyziInstallmentResource->load($model, $id, 'category_id');
                if ($model->getCategoryId()) {
                    $this->installmentModel = $model;
                }
            }
        }

        return $this->installmentModel;
    }

    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $installment = $this->getInstallment();

        if ($installment && $installment->getCategoryId()) {
            $data = [
                'label' => __('Sil'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\''.__(
                        'Bu taksit ayarını silmek istediğinize emin misiniz?'
                    ).'\', \''.$this->getDeleteUrl().'\')',
                'sort_order' => 20,
            ];
        }

        return $data;
    }

    /**
     * Get URL for delete button
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $installment = $this->getInstallment();
        if ($installment && $installment->getCategoryId()) {
            return $this->urlBuilder->getUrl('*/*/delete', ['id' => $installment->getCategoryId()]);
        }
        return '';
    }
}
