<?php

namespace Iyzico\Iyzipay\Controller\Adminhtml\ProductInstallmentSettings;

use Exception;
use Iyzico\Iyzipay\Model\IyziInstallment;
use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class Save extends Action
{
    /**
     * @var DataPersistorInterface
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @var IyziInstallmentFactory
     */
    protected IyziInstallmentFactory $iyziInstallmentFactory;

    /**
     * @var Registry
     */
    protected Registry $coreRegistry;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param IyziInstallmentFactory $iyziInstallmentFactory
     */
    public function __construct(
        Context                $context,
        Registry               $coreRegistry,
        DataPersistorInterface $dataPersistor,
        IyziInstallmentFactory $iyziInstallmentFactory
    )
    {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('installment_id');

            $model = $this->iyziInstallmentFactory->create();
            if ($id) {
                try {
                    $model->load($id);
                } catch (Exception $e) {
                    $this->messageManager->addErrorMessage(__('Bu kayıt artık mevcut değil.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            if (empty($data['installment_id'])) {
                $data['installment_id'] = null;
            }

            $model->setData('product_sku', $data['product_sku'] ?? null);

            $installmentOptions = [];

            if (isset($data['data']) && isset($data['data']['installment_options'])) {
                $installmentOptions = $data['data']['installment_options'];
            } elseif (isset($data['installment_options'])) {
                $installmentOptions = $data['installment_options'];
            }

            // Taksit seçeneklerini JSON olarak kaydet
            $model->setData('settings', json_encode($installmentOptions));

            if (isset($data['installment_id']) && $data['installment_id']) {
                $model->setData('installment_id', $data['installment_id']);
            }

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('Ürün SKU taksit ayarları başarıyla kaydedildi.'));
                $this->dataPersistor->clear('iyzico_installment');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Kaydederken bir hata oluştu.'));
            }

            $this->dataPersistor->set('iyzico_installment', $data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('installment_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Is the user allowed to view the page.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Iyzico_Iyzipay::product_installment_settings');
    }
}
