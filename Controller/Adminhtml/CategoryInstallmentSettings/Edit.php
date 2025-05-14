<?php

namespace Iyzico\Iyzipay\Controller\Adminhtml\CategoryInstallmentSettings;

use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var IyziInstallmentFactory
     */
    protected $iyziInstallmentFactory;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param IyziInstallmentFactory $iyziInstallmentFactory
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        IyziInstallmentFactory $iyziInstallmentFactory,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->iyziInstallmentFactory->create();

        if ($id) {
            $model->load($id, 'category_id');
            if (!$model->getCategoryId()) {
                $this->messageManager->addErrorMessage(__('Bu kayıt artık mevcut değil.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('iyzico_installment', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Kategori Taksit Ayarları'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getCategoryId() ? __('Taksit Ayarını Düzenle') : __('Yeni Taksit Ayarı')
        );

        return $resultPage;
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
