<?php

namespace Iyzico\Iyzipay\Controller\Adminhtml\CategoryInstallmentSettings;

use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Action
{
    /**
     * @var IyziInstallmentFactory
     */
    protected $iyziInstallmentFactory;

    /**
     * @param Context $context
     * @param IyziInstallmentFactory $iyziInstallmentFactory
     */
    public function __construct(
        Context $context,
        IyziInstallmentFactory $iyziInstallmentFactory
    ) {
        parent::__construct($context);
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            try {
                $model = $this->iyziInstallmentFactory->create();
                $model->load($id, 'category_id');
                $model->delete();

                $this->messageManager->addSuccessMessage(__('Taksit ayarı başarıyla silindi.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('Silmek istediğiniz taksit ayarı bulunamadı.'));
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