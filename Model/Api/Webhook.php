<?php

namespace Iyzico\Iyzipay\Model\Api;

use Exception;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Sales\Model\OrderFactory;
use Iyzico\Iyzipay\Api\WebhookInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Iyzico\Iyzipay\Helper\webhookHelper;
use Iyzico\Iyzipay\Controller\Response\IyzicoCheckoutForm;



/**
 * Class Webhook
 *
 * @package Iyzico\Iyzipay\Model\Api
 */
class Webhook implements WebhookInterface
{
  protected $orderFactory;
  protected $config;
  protected $transactionBuilder;
  protected $transactionRepository;
  protected $request;
  protected $webhookHelper;
  protected $IyzicoCheckoutForm;

  /**
   * Webhook constructor.
   *
   * @param OrderFactory                   $orderFactory
   * @param Context                        $context
   * @param TransactionBuilder             $tb
   * @param TransactionRepositoryInterface $transactionRepository
   * @param Request                        $request
   * @param webhookHelper                  $webhookHelper
   * @param IyzicoCheckoutForm             $IyzicoCheckoutForm
   */
  public function __construct(
      OrderFactory $orderFactory,
      Context $context,
      TransactionBuilder $tb,
      TransactionRepositoryInterface $transactionRepository,
      Request $request,
      webhookHelper $webhookHelper,
      IyzicoCheckoutForm  $IyzicoCheckoutForm
  ) {
      $this->orderFactory             = $orderFactory;
      $this->config                   = $context->getScopeConfig();
      $this->transactionBuilder       = $tb;
      $this->transactionRepository    = $transactionRepository;
      $this->request                  = $request;
      $this->webhookHelper            = $webhookHelper;
      $this->IyzicoCheckoutForm       = $IyzicoCheckoutForm;
  }
  /**
     * @return string
     * @throws Exception
     */
  public function getResponse($webhookUrlKey): string {
    $uniqWebhookUrlKey = $this->webhookHelper->getWebhookUrl();
    if($webhookUrlKey != $uniqWebhookUrlKey)
    {
      return $this->webhookHelper->webhookHttpResponse("invalid_parameters - Webhook Url Key Error" , 404);

    }

    $body = @file_get_contents('php://input');
    //header('Content-Type: application/json');
    $response = json_decode($body);

    if (isset($response->iyziEventType) && isset($response->token) && isset($response->paymentConversationId))
    {
      $paymentConversationId = $response->paymentConversationId;
      $token = $response->token;
      $iyziEventType = $response->iyziEventType;
      $createIyzicoSignature = base64_encode(sha1($this->webhookHelper->getSecretKey() . $iyziEventType . $token, true));
      if($createIyzicoSignature)
      {

        return $this->gethttpResponse($response);

      }
      else {
        return  $this->webhookHelper->webhookHttpResponse("signature_not_valid - X-IYZ-SIGNATURE geçersiz" , 404);
      }
    }
    else {

      return  $this->webhookHelper->webhookHttpResponse("invalid_parameters - Gönderilen parametreler geçersiz" , 404);
    }

    }

    /**
     * @param  $response
     * @return string
     */
    public function gethttpResponse($response): string
    {
      $webhook = 'webhook';
      $webhookPaymentConversationId = $response->paymentConversationId;
      $webhookToken = $response->token;
      $webhookIyziEventType = $response->iyziEventType;

      return $this->IyzicoCheckoutForm->iyzicoResponse($webhook , $webhookPaymentConversationId , $webhookToken , $webhookIyziEventType);

    }


    /**
     * @return mixed
     */
    public function gethttpResponses()
    {
      return 'ok';

    }








}
