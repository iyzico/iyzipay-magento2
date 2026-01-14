<?php
namespace Iyzico\Iyzipay\Controller\Adminhtml\ProductInstallmentSettings;

use Exception;
use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment as IyziInstallmentResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Action
{
    /**
     * @var IyziInstallmentFactory
     */
    protected IyziInstallmentFactory $iyziInstallmentFactory;

    /**
     * @var IyziInstallmentResource
     */
    protected IyziInstallmentResource $iyziInstallmentResource;

    /**
     * @param  Context  $context
     * @param  IyziInstallmentFactory  $iyziInstallmentFactory
     * @param  IyziInstallmentResource  $iyziInstallmentResource
     */
    public function __construct(
        Context $context,
        IyziInstallmentFactory $iyziInstallmentFactory,
        IyziInstallmentResource $iyziInstallmentResource
    ) {
        parent::__construct($context);
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->iyziInstallmentResource = $iyziInstallmentResource;
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
                $this->iyziInstallmentResource->load($model, $id);

                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('Silinecek bir kayıt bulunamadı.'));
                    return $resultRedirect->setPath('*/*/');
                }

                $this->iyziInstallmentResource->delete($model);
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
