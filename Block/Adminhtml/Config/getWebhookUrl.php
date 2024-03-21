<?php

namespace Iyzico\Iyzipay\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class webhookurl
 *
 * @package Iyzico\Iyzipay\Block\Adminhtml\System\Config\Fieldset
 */
class getWebhookUrl extends Field
{

    /**
     * @param  AbstractElement $element
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {

        $webhookUrlKey = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key');
        if(isset($webhookUrlKey))
        {

          return $this->_storeManager->getStore()->getBaseUrl().'rest/V1/iyzico/webhook/'.$webhookUrlKey.'<br>'.$this->iyzicoWebhookSubmitbutton();
        }
        else {
          return 'clear cookies and later "save config  button" push';
        }

    }


    public  function iyzicoWebhookSubmitbutton()
    {

      $webhookButtonSet = $this->_scopeConfig->getValue('payment/iyzipay/webhook_url_key_active');
      if($webhookButtonSet == 2)
      {
        $htmlButton = '<form action="#" method="post">
                      <button class="btn btn-light" type="submit" name="button">Active</button> <a href="mailto:entegrasyon@iyzico.com">entegrasyon@iyzico.com</a>
                      </form>    ';

         $post_data = $this->getRequest()->getPost();
          if(isset($post_data))
         {
           $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
           $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
           $connection = $resource->getConnection();
           $tableName = $resource->getTableName('core_config_data'); //gives table name with prefix
           $sql = "Update " . $tableName." Set value = '0' Where path = 'payment/iyzipay/webhook_url_key_active'";
           $result = $connection->query($sql);
        }
        return $htmlButton;

      }

    }
}
