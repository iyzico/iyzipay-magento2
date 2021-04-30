<?php

namespace Iyzico\PayWithIyzico\Controller\Response;

use Iyzico\PayWithIyzico\Controller\IyzicoBase\IyzicoFormObjectGenerator;
use Iyzico\PayWithIyzico\Controller\IyzicoBase\IyzicoResponseObjectGenerator;
use Iyzico\PayWithIyzico\Controller\IyzicoBase\IyzicoPkiStringBuilder;
use Iyzico\PayWithIyzico\Controller\IyzicoBase\IyzicoRequest;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class PayWithIyzico extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_checkoutSession;
    protected $_customerSession;
    protected $_quote;
    protected $_cartManagement;
    protected $_resultRedirect;
    protected $_resultJsonFactory;
    protected $_quoteRepository;
    protected $_scopeConfig;
    protected $_iyziOrderFactory;
    protected $_iyziCardFactory;
    protected $_request;
    protected $_messageManager;
    protected $_guestCartManagement;
    protected $_storeManager;
    protected $_helper;

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Iyzico\PayWithIyzico\Model\IyziOrderFactory $iyziOrderFactory,
        \Iyzico\PayWithIyzico\Model\IyziCardFactory $iyziCardFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Api\GuestPaymentInformationManagementInterface $guestCartManagement,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Iyzico\PayWithIyzico\Helper\IyzicoHelper $helper
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_pageFactory = $pageFactory;
        $this->_jsonEncoder = $encoder;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_quote = $quote;
        $this->_cartManagement = $cartManagement;
        $this->_resultRedirect = $context->getResultFactory();
        $this->_quoteRepository = $quoteRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->_iyziOrderFactory = $iyziOrderFactory;
        $this->_iyziCardFactory = $iyziCardFactory;
        $this->_request = $request;
        $this->_messageManager = $messageManager;
        $this->_guestCartManagement = $guestCartManagement;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
    }

    public function execute()
    {

        $postData = $this->getRequest()->getPostValue();
        $resultRedirect = $this->_resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        $this->_quote = $this->_checkoutSession->getQuote();

        if(!isset($postData['token'])) {

            $errorMessage = __('Token not found');
            $this->_messageManager->addError($errorMessage);
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            return $resultRedirect;

        }

       if($this->_customerSession->getIyziToken() != $postData['token'] && !isset($_POST['iyziEventType'])) {

            $errorMessage = __('Token Not Match');
            $this->_messageManager->addError($errorMessage);
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            return $resultRedirect;

        }



        $token = $postData['token']; /* Add Filterr */
        $customerId = 0;
        $apiKey = $this->_scopeConfig->getValue('payment/iyzipay/api_key');
        $secretKey = $this->_scopeConfig->getValue('payment/iyzipay/secret_key');
        $sandboxStatus = $this->_scopeConfig->getValue('payment/iyzipay/sandbox');
        $rand = uniqid();
        $baseUrl = 'https://api.iyzipay.com';
        $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $this->_quote->setIyziCurrency($currency);


        if($sandboxStatus)
            $baseUrl = 'https://sandbox-api.iyzipay.com';

        $iyzicoResponseObject = new IyzicoResponseObjectGenerator();
        $iyzicoPkiStringBuilder = new IyzicoPkiStringBuilder();
        $iyzicoRequest = new IyzicoRequest();

        if($this->_customerSession->isLoggedIn()){
            $customerId = $this->_customerSession->getCustomerId();
        }

        $tokenDetailObject        = $iyzicoResponseObject->generateTokenDetailObject('123456789',$token);
        $iyzicoPkiString          = $iyzicoPkiStringBuilder->pkiStringGenerate($tokenDetailObject);
        $authorization            = $iyzicoPkiStringBuilder->authorizationGenerate($iyzicoPkiString,$apiKey,$secretKey,$rand);
        $iyzicoJson               = json_encode($tokenDetailObject,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $requestResponse          = $iyzicoRequest->iyzicoCheckoutFormDetailRequest($baseUrl,$iyzicoJson,$authorization);

        //BANK TRANSFER COFORM NOTIFICATION CONTROL
        if (isset($_POST['token']) && isset($_POST['iyziEventType']) && $_POST['iyziEventType'] == 'BANK_TRANSFER_AUTH'){
            if ($requestResponse->status == 'success'){
                if ($requestResponse->paymentStatus == 'SUCCESS'){

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $order = $objectManager->create('\Magento\Sales\Model\Order')
                        ->getCollection()->addFieldToFilter('quote_id', array("eq" => (int)$requestResponse->basketId));

                    if ($order->getData() && $order->getData()[0]['state'] == "pending") {

                        $salesOrderId = $order->getData()[0]['entity_id'];
                        $order = $objectManager->create('\Magento\Sales\Model\Order')
                            ->load($salesOrderId);

                        $order->setState("processing")->setStatus("processing");
                        $bankTransferComment = __('Bank Transfer success.');

                        $order->addStatusHistoryComment($bankTransferComment)->setIsVisibleOnFront(true);

                        $order->save();
                        return $this->httpResponseForNotification(200,"OK");
                    }
                    else{
                        return $this->httpResponseForNotification(404, "ERROR1");
                    }
                }
                else{
                    return $this->httpResponseForNotification(404, "ERROR2");
                }
            }
            else{
                return $this->httpResponseForNotification(404,"ERROR3");
            }
        }

        $requestResponse->paymentId = isset($requestResponse->paymentId) ? (int) $requestResponse->paymentId : '';
        $requestResponse->paidPrice = isset($requestResponse->paidPrice) ? (float) $requestResponse->paidPrice : '';
        $requestResponse->basketId =  isset($requestResponse->basketId) ? (int) $requestResponse->basketId : '';

        /* Insert Order Log */
        $iyziOrderModel = $this->_iyziOrderFactory->create();
        $iyziOrderModel->setData('payment_id',$requestResponse->paymentId);
        $iyziOrderModel->setData('total_amount',$requestResponse->paidPrice);
        $iyziOrderModel->setData('order_id',$requestResponse->basketId);
        $iyziOrderModel->setData('status',$requestResponse->status);
        $iyziOrderModel->save($iyziOrderModel);

        /* Error Redirect Start */
        if(($requestResponse->paymentStatus != 'SUCCESS' && $requestResponse->paymentStatus != 'INIT_BANK_TRANSFER') || $requestResponse->status != 'success') {

            $errorMessage = isset($requestResponse->errorMessage) ? $requestResponse->errorMessage : 'Failed';

            if($requestResponse->status == 'success' && $requestResponse->paymentStatus == 'FAILURE') {
                $errorMessage = __('3D Security Error');

            }

            /* Redirect Error */
            $this->_messageManager->addError($errorMessage);
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            return $resultRedirect;

        }

        /* Order ID Confirmation */
        if($this->_quote->getId() != $requestResponse->basketId) {

            $errorMessage = __('Order Not Match');

            /* Redirect Error */
            $this->_messageManager->addError($errorMessage);
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            return $resultRedirect;
        }

        /* Order Price Confirmation */
        $totalPrice = $this->_helper->priceParser(round($this->_quote->getGrandTotal(),2));
        if($totalPrice > $requestResponse->paidPrice) {
            /* Cancel Payment */
            $errorMessage = __('Order Price Not Match');

            /* Redirect Error */
            $this->_messageManager->addError($errorMessage);
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            return $resultRedirect;
        }

        /* Error Redirect End */

        $this->_quote->setPayWithIyzicoPaymentStatus('success');

        if($requestResponse->paymentStatus == 'INIT_BANK_TRANSFER' && $requestResponse->status == 'success') {
            $this->_quote->setPayWithIyzicoPaymentStatus('pending');
    }

        /* Card Save */
        if($customerId) {
            if(isset($requestResponse->cardUserKey)) {
                $iyziCardFind = $this->_iyziCardFactory->create()->getCollection()
                                                ->addFieldToFilter('customer_id',$customerId)
                                                ->addFieldToFilter('api_key',$apiKey)
                                                ->addFieldToSelect('card_user_key');

                $iyziCardFind = $iyziCardFind->getData();

                $customerCardUserKey = !empty($iyziCardFind[0]['card_user_key']) ? $iyziCardFind[0]['card_user_key'] : null;

                if($requestResponse->cardUserKey != $customerCardUserKey) {

                    /* Customer Card Save */
                    $iyziCardModel = $this->_iyziCardFactory->create();
                    $iyziCardModel->setData('customer_id',$customerId);
                    $iyziCardModel->setData('card_user_key',$requestResponse->cardUserKey);
                    $iyziCardModel->setData('api_key',$apiKey);
                    $iyziCardModel->save($iyziCardModel);
                }
            }
        }

        $this->_quote->getPayment()->setMethod('paywithiyzico');
        $installmentFee = 0;

        if (isset($requestResponse->installment) && !empty($requestResponse->installment) && $requestResponse->installment > 1) {

            $installmentFee = $requestResponse->paidPrice - $this->_quote->getGrandTotal();
            $this->_quote->setInstallmentFee($installmentFee);
            $this->_quote->setInstallmentCount($requestResponse->installment);

        }

        /* Set Payment Id */
        $this->_quote->setIyzicoPaymentId($requestResponse->paymentId);

        if($this->_customerSession->isLoggedIn()) {
            /* Place Order - Login Checkout */
            $this->_cartManagement->placeOrder($this->_quote->getId());

        } else {

            $this->_quote->setCheckoutMethod($this->_cartManagement::METHOD_GUEST);
            $this->_quote->setCustomerEmail($this->_customerSession->getEmail());

            $this->_cartManagement->placeOrder($this->_quote->getId());

        }

        $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
        return $resultRedirect;

    }

    public function httpResponseForNotification($responseCode, $message){
        $resultPage = $this->resultFactory
            ->create(ResultFactory::TYPE_JSON)
            ->setData(['message'=>$message])
            ->setHttpResponseCode($responseCode);

        return $resultPage;
    }
}
