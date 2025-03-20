<?php

namespace Iyzico\Iyzipay\Controller\Response;


use Exception;
use Iyzico\Iyzipay\Helper\IyzicoHelper;
use Iyzico\Iyzipay\Model\IyziCardFactory;
use Iyzico\Iyzipay\Model\IyziOrderFactory;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Options;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;


class IyzicoCheckoutForm extends Action implements CsrfAwareActionInterface
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

    public function __construct(
        Context                                    $context,
        EncoderInterface                           $encoder,
        PageFactory                                $pageFactory,
        CheckoutSession                            $checkoutSession,
        Session                                    $customerSession,
        Quote                                      $quote,
        CartManagementInterface                    $cartManagement,
        ResultFactory                              $resultFactory,
        JsonFactory                                $resultJsonFactory,
        CartRepositoryInterface                    $quoteRepository,
        ScopeConfigInterface                       $scopeConfig,
        IyziOrderFactory                           $iyziOrderFactory,
        IyziCardFactory                            $iyziCardFactory,
        Http                                       $request,
        ManagerInterface                           $messageManager,
        GuestPaymentInformationManagementInterface $guestCartManagement,
        StoreManagerInterface                      $storeManager,
        IyzicoHelper                               $helper


    )
    {
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

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        return $this->iyzicoResponse();
    }


    /**
     * @param  $webhook
     * @param  $webhookPaymentConversationId
     * @param  $webhookToken
     * @param  $webhookIyziEventType
     * @return mixed
     */
    public function iyzicoResponse($webhook = null, $webhookPaymentConversationId = null, $webhookToken = null, $webhookIyziEventType = null)
    {
        try {
            $postData = $this->getRequest()->getPostValue();
            $resultRedirect = $this->_resultRedirect->create(ResultFactory::TYPE_REDIRECT);
            $this->_quote = $this->_checkoutSession->getQuote();

            if (!isset($postData['token']) && $webhook != 'webhook') {
                $errorMessage = __('Token not found');
                $this->_messageManager->addError($errorMessage);
                $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
                return $resultRedirect;
            }

            $conversationId = "";
            if ($webhook == 'webhook') {
                $token = $webhookToken;
                $conversationId = $webhookPaymentConversationId;
            } else {
                $token = $postData['token'];
            }

            $customerId = 0;
            $apiKey = $this->_scopeConfig->getValue('payment/iyzipay/api_key');
            $secretKey = $this->_scopeConfig->getValue('payment/iyzipay/secret_key');
            $sandboxStatus = $this->_scopeConfig->getValue('payment/iyzipay/sandbox');
            $baseUrl = $sandboxStatus ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';
            $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
            $this->_quote->setIyziCurrency($currency);
            $storeId = $this->_storeManager->getStore()->getId();
            $locale = $this->_helper->cutLocale($this->_scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId));

            if ($this->_customerSession->isLoggedIn()) {
                $customerId = $this->_customerSession->getCustomerId();
            }

            $options = new Options();
            $options->setSecretKey($secretKey);
            $options->setApiKey($apiKey);
            $options->setBaseUrl($baseUrl);

            $request = new RetrieveCheckoutFormRequest();
            $request->setLocale($locale);
            $request->setToken($token);
            $request->setConversationId($conversationId);

            $response = CheckoutForm::retrieve($request, $options);

            if ($webhook == 'webhook' && $response->getStatus() == 'failure' && $response->getPaymentStatus() != 'SUCCESS') {
                return $this->webhookHttpResponse($response->getErrorCode() . '-' . $response->getErrorMessage(), 404);
            }

            $objectManager = ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();

            if ($webhook == 'webhook' && $response->getStatus() == 'success' && $response->getPaymentStatus() == 'SUCCESS') {
                $tableName = $resource->getTableName('sales_order');
                $sql = "Select * FROM " . $tableName . " Where quote_id = " . $response->getBasketId();
                $result = $connection->fetchAll($sql);

                if ($webhookIyziEventType == 'BANK_TRANSFER_AUTH' && $response->getStatus() == 'success') {
                    $entity_id = $result[0]['entity_id'];
                    $order = $objectManager->create('\Magento\Sales\Model\Order')->load($entity_id);
                    $order->setState('processing');
                    $order->setStatus('processing');
                    $historyComment = 'Bank Transfer success.';
                    $order->addStatusHistoryComment($historyComment);
                    $order->save();
                    return 'ok';
                }
                if (!empty($result)) {
                    return $this->webhookHttpResponse("Order Exist - Sipariş zaten var.", 200);
                }
            }

            if ($webhook == 'webhook') {
                $tableName = $resource->getTableName('sales_order');
                $sql = "Select * FROM " . $tableName . " Where quote_id = " . $response->getBasketId();
                $result = $connection->fetchAll($sql);
                $entity_id = $result[0]['entity_id'];
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($entity_id);

                if ($webhookIyziEventType == 'CREDIT_PAYMENT_PENDING' && $response->getPaymentStatus() == 'PENDING_CREDIT') {
                    $order->setState('pending');
                    $order->setStatus('pending');
                    $historyComment = 'Alışveriş kredisi başvurusu sürecindedir.';
                    $order->addStatusHistoryComment($historyComment);
                    $order->save();
                    return 'ok';
                }

                if ($webhookIyziEventType == 'CREDIT_PAYMENT_AUTH' && $response->getStatus() == 'success') {
                    $order->setState('processing');
                    $order->setStatus('processing');
                    $historyComment = 'Alışveriş kredisi işlemi başarıyla tamamlandı.';
                    $order->addStatusHistoryComment($historyComment);
                    $order->save();
                    return 'ok';

                }
                
                if ($webhookIyziEventType == 'CREDIT_PAYMENT_INIT' && $response->getStatus() == 'INIT_CREDIT') {
                    $order->setState('pending');
                    $order->setStatus('pending');
                    $historyComment = 'Alışveriş kredisi işlemi başlatıldı.';
                    $order->addStatusHistoryComment($historyComment);
                    $order->save();
                    return 'ok';

                }
                if ($webhookIyziEventType == 'CREDIT_PAYMENT_AUTH' && $response->getStatus() == 'FAILURE') {
                    $order->setState('canceled');
                    $order->setStatus('canceled');
                    $historyComment = 'Alışveriş kredisi işlemi başarısız.';
                    $order->addStatusHistoryComment($historyComment);
                    $order->save();
                    return 'ok';
                }

            }


            if ($webhook != 'webhook' && $response->getPaymentStatus() == 'PENDING_CREDIT' && $response->getStatus() == 'success') {
                $status = 'PENDING_CREDIT';
            } else {
                $status = $response->getStatus();
            }

            /* Insert Order Log */
            $iyziOrderModel = $this->_iyziOrderFactory->create();
            $iyziOrderModel->setData('payment_id', $response->getPaymentId());
            $iyziOrderModel->setData('total_amount', $response->getPaidPrice());
            $iyziOrderModel->setData('order_id', $response->getBasketId());
            $iyziOrderModel->setData('status', $status);
            $iyziOrderModel->save($iyziOrderModel);

            /*Bank Transfer */
            if ($response->getPaymentStatus() == 'INIT_BANK_TRANSFER' && $response->getStatus() == 'success') {
                $this->_quote->setCheckoutMethod($this->_cartManagement::METHOD_GUEST);
                $this->_cartManagement->placeOrder($this->_quote->getId());
                $this->_quote->setIyzicoPaymentId($response->getPaymentId());

                $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
                return $resultRedirect;
            }

            /* credit shipping */
            if ($webhook != 'webhook' && $response->getPaymentStatus() == 'PENDING_CREDIT' && $response->getStatus() == 'success') {
                $this->_quote->setCheckoutMethod($this->_cartManagement::METHOD_GUEST);
                $this->_quote->setIyziPaymentStatus('PENDING_CREDIT');
                $this->_quote->setIyzicoPaymentId($response->getPaymentId());
                $this->_cartManagement->placeOrder($this->_quote->getId());
                $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
                return $resultRedirect;
            }


            /* Error Redirect Start */
            if ($response->getPaymentStatus() != 'SUCCESS' || $response->getStatus() != 'success') {
                $errorMessage = $response->getErrorMessage() ?? 'Failed';
                if ($response->getStatus() == 'success' && $response->getPaymentStatus() == 'FAILURE') {
                    $errorMessage = __('3D Security Error');

                }
                /* Redirect Error */
                $this->_messageManager->addError($errorMessage);
                $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
                return $resultRedirect;
            }


            /* Order ID Confirmation */
            if ($this->_quote->getId() != $response->getBasketId() && $webhook != 'webhook') {
                $errorMessage = __('Order Not Match');
                /* Redirect Error */
                $this->_messageManager->addError($errorMessage);
                $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
                return $resultRedirect;
            }


            /* Order Price Confirmation */
            $totalPrice = $this->_helper->priceParser(round($this->_quote->getGrandTotal(), 2));
            if ($totalPrice > $response->getPaidPrice()) {
                /* Cancel Payment */
                $errorMessage = __('Order Price Not Match');

                /* Redirect Error */
                $this->_messageManager->addError($errorMessage);
                $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
                return $resultRedirect;
            }


            if ($webhook != 'webhook' && $response->getPaymentStatus() == 'PENDING_CREDIT' && $response->getStatus() == 'success') {
                $this->_quote->setIyziPaymentStatus('PENDING_CREDIT');
                $this->_quote->setIyzicoPaymentId($response->getPaymentId());
            } else {
                $this->_quote->setIyziPaymentStatus('success');
            }

            /* Card Save */
            if ($customerId) {
                if ($response->getCardUserKey() !== null) {
                    $iyziCardFind = $this->_iyziCardFactory->create()->getCollection()
                        ->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('api_key', $apiKey)
                        ->addFieldToSelect('card_user_key');

                    $iyziCardFind = $iyziCardFind->getData();

                    $customerCardUserKey = !empty($iyziCardFind[0]['card_user_key']) ? $iyziCardFind[0]['card_user_key'] : null;

                    if ($response->getCardUserKey() != $customerCardUserKey) {
                        /* Customer Card Save */
                        $iyziCardModel = $this->_iyziCardFactory->create();
                        $iyziCardModel->setData('customer_id', $customerId);
                        $iyziCardModel->setData('card_user_key', $response->getCardUserKey());
                        $iyziCardModel->setData('api_key', $apiKey);
                        $iyziCardModel->save($iyziCardModel);
                    }
                }
            }

            $this->_quote->getPayment()->setMethod('iyzipay');
            $installmentFee = 0;

            if (!empty($response->getInstallment()) && $response->getInstallment() > 1) {
                $installmentFee = $response->getPaidPrice() - $this->_quote->getGrandTotal();
                $this->_quote->setInstallmentFee($installmentFee);
                $this->_quote->setInstallmentCount($response->getInstallment());
            }

            /* Set Payment Id */
            $this->_quote->setIyzicoPaymentId($response->getPaymentId());

            if ($webhook == 'webhook' && $response->getStatus() == 'success' && $response->getPaymentStatus() == 'SUCCESS') {
                try {
                    $this->_quote->setCheckoutMethod($this->_cartManagement::METHOD_GUEST);
                    $this->_quote->setCustomerEmail($this->_customerSession->getEmail());
                    $this->_cartManagement->placeOrder($response->getBasketId());
                    return $this->webhookHttpResponse("Order Created by Webhook - Sipariş webhook tarafından oluşturuldu.", 200);
                } catch (Exception $e) {
                    return $this->webhookHttpResponse("Order Created by Webhook - Sipariş webhook tarafından oluşturuldu.", 200);
                }

            }

            if ($this->_customerSession->isLoggedIn()) {
                /* Place Order - Login Checkout */
                $this->_cartManagement->placeOrder($this->_quote->getId());
            } else {
                $this->_quote->setCheckoutMethod($this->_cartManagement::METHOD_GUEST);
                $this->_quote->setCustomerEmail($this->_customerSession->getEmail());
                $this->_cartManagement->placeOrder($this->_quote->getId());
            }

            $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
            return $resultRedirect;
        } catch (Exception $e) {
            if ($webhook == 'webhook') {
                return $this->webhookHttpResponse($response->getErrorCode() . '-' . $response->getErrorMessage(), 404);
            }
            /* Redirect Error */
            $this->_messageManager->addError($e);
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
            return $resultRedirect;
        }


    }

    /**
     * @param  $message
     * @param  $status
     * @return mixed
     */
    public function webhookHttpResponse($message, $status)
    {
        $httpMessage = array('message' => $message, 'status' => $status);
        header('Content-Type: application/json, Status: ' . $status, true, $status);
        echo json_encode($httpMessage);
        exit();

    }
}
