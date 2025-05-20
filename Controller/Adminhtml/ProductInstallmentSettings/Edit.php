<?php

namespace Iyzico\Iyzipay\Controller\Adminhtml\ProductInstallmentSettings;

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
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Bu kayıt artık mevcut değil.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('iyzico_installment', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Ürün SKU Taksitlendirme'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Taksitlendirmeyi Düzenle') : __('Yeni Taksitlendirme')
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
        return $this->_authorization->isAllowed('Iyzico_Iyzipay::product_installment_settings');
    }
}
