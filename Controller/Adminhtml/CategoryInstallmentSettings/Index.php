<?php

namespace Iyzico\Iyzipay\Controller\Adminhtml\CategoryInstallmentSettings;

use Iyzico\Iyzipay\Model\IyziInstallmentFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @var IyziInstallmentFactory
     */
    protected IyziInstallmentFactory $iyziInstallmentFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context                $context,
        PageFactory            $resultPageFactory,
        IyziInstallmentFactory $iyziInstallmentFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->iyziInstallmentFactory = $iyziInstallmentFactory;
    }

    /**
     * Index action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Kategori Taksit Ayarları'));

        return $resultPage;
    }

    /**
     * Is the user allowed to view the page.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Iyzico_Iyzipay::category_installment_settings');
    }
}
