<?php

namespace Iyzico\Iyzipay\Controller\Adminhtml\ProductInstallmentSettings;

use Exception;
use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;

class Delete extends Action
{
    /**
     * @var Registry
     */
    protected Registry $coreRegistry;

    /**
     * @var IyziInstallmentFactory
     */
    protected IyziInstallmentFactory $iyziInstallmentFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param IyziInstallmentFactory $iyziInstallmentFactory
     */
    public function __construct(
        Context                $context,
        Registry               $coreRegistry,
        IyziInstallmentFactory $iyziInstallmentFactory
    )
    {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->iyziInstallmentFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('Kayıt silindi.'));
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('Silinecek bir kayıt bulunamadı.'));
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
