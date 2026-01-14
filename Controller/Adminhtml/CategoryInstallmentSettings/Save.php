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

namespace Iyzico\Iyzipay\Controller\Adminhtml\CategoryInstallmentSettings;

use Exception;
use Iyzico\Iyzipay\Logger\IyziErrorLogger;
use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var IyziInstallmentFactory
     */
    protected $iyziInstallmentFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var IyziErrorLogger
     */
    protected $iyziLogger;

    /**
     * @param  Context  $context
     * @param  DataPersistorInterface  $dataPersistor
     * @param  IyziInstallmentFactory  $iyziInstallmentFactory
     * @param  CollectionFactory  $collectionFactory
     * @param  IyziErrorLogger  $logger
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        IyziInstallmentFactory $iyziInstallmentFactory,
        CollectionFactory $collectionFactory,
        IyziErrorLogger $logger
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->collectionFactory = $collectionFactory;
        $this->iyziLogger = $logger;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParams();

        $this->iyziLogger->info('POST VERİSİ: '.print_r($data, true));

        if ($data) {
            $model = $this->iyziInstallmentFactory->create();

            $categoryId = isset($data['category_id']) ? $data['category_id'] : null;

            if (!$categoryId) {
                $this->messageManager->addErrorMessage(__('Kategori ID bulunamadı.'));
                return $resultRedirect->setPath('*/*/');
            }

            // Önce yeni kategori mi yoksa mevcut kategori mi kontrol edelim
            $collection = $this->collectionFactory->create();
            $existingModel = $collection
                ->addFieldToFilter('category_id', $categoryId)
                ->getFirstItem();

            if ($existingModel && $existingModel->getId()) {
                $model = $existingModel;
            }

            // Kategori ID'sini ayarla
            $model->setData('category_id', $categoryId);

            // Taksit seçeneklerini al
            $installmentOptions = [];

            if (isset($data['data']) && isset($data['data']['installment_options'])) {
                $installmentOptions = $data['data']['installment_options'];
                $this->iyziLogger->info('Taksitler data.installment_options\'dan alındı: '.print_r($installmentOptions,
                        true));
            } elseif (isset($data['installment_options'])) {
                $installmentOptions = $data['installment_options'];
                $this->iyziLogger->info('Taksitler installment_options\'dan alındı: '.print_r($installmentOptions,
                        true));
            }

            // Taksit seçeneklerini JSON olarak kaydet
            $model->setData('settings', json_encode($installmentOptions));

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('Taksit ayarları başarıyla kaydedildi.'));
                $this->dataPersistor->clear('iyzico_installment');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getCategoryId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->iyziLogger->error('Hata: '.$e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e,
                    __('Taksit ayarları kaydedilirken hata: %1', $e->getMessage()));
                $this->iyziLogger->error('Hata: '.$e->getMessage());
            }

            $this->dataPersistor->set('iyzico_installment', $data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $categoryId]);
        }

        $this->messageManager->addErrorMessage(__('Geçersiz form verisi.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Iyzico_Iyzipay::category_installment_settings');
    }
}
