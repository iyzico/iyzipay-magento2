<?php
namespace Iyzico\Iyzipay\Controller\Adminhtml\ProductInstallmentSettings;

use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Iyzico\Iyzipay\Model\ResourceModel\IyziInstallment as IyziInstallmentResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
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
     * @var IyziInstallmentResource
     */
    protected $iyziInstallmentResource;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param  Context  $context
     * @param  PageFactory  $resultPageFactory
     * @param  IyziInstallmentFactory  $iyziInstallmentFactory
     * @param  IyziInstallmentResource  $iyziInstallmentResource
     * @param  DataPersistorInterface  $dataPersistor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        IyziInstallmentFactory $iyziInstallmentFactory,
        IyziInstallmentResource $iyziInstallmentResource,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
        $this->iyziInstallmentResource = $iyziInstallmentResource;
        $this->dataPersistor = $dataPersistor;
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
            $this->iyziInstallmentResource->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Bu kayıt artık mevcut değil.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // Store the model data in DataPersistor instead of Registry
        $this->dataPersistor->set('iyzico_installment', $model);

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
