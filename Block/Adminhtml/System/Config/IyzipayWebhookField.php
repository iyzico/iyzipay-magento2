<?php

/**
 * iyzico Payment Gateway For Magento 2
 * Copyright (C) 2018 iyzico
 *
 * This file is part of Iyzico/Iyzipay.
 *
 * Iyzico/Iyzipay is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Iyzico\Iyzipay\Block\Adminhtml\System\Config;

use Iyzico\Iyzipay\Helper\ConfigHelper;
use Iyzico\Iyzipay\Logger\IyziWebhookLogger;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Class GetWebhookUrlField
 *
 * @package Vendor\Module\Block\Adminhtml\Config
 * @extends Field
 *
 * This class is used in etc/adminhtml/system.xml
 */
class IyzipayWebhookField extends Field
{
    private SecureHtmlRenderer $secureRenderer;

    public function __construct(
        protected Context $context,
        protected ConfigHelper $configHelper,
        protected IyziWebhookLogger $logger,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data);
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
    }

    /**
     * Retrieve the webhook URL and submit button HTML
     *
     * @param  AbstractElement  $element
     * @return string
     * @throws NoSuchEntityException|LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $webhookUrlKey = $this->configHelper->getWebhookUrlKey();
        $websiteId = $this->getRequest()->getParam('website');
        $baseUrl = $this->configHelper->getWebsiteBaseUrl($websiteId);

        if ($webhookUrlKey) {
            return $baseUrl.'rest/V1/iyzico/webhook/'.$webhookUrlKey.'<br>'.$this->getWebhookSubmitButtonHtml();
        } else {
            return 'Clear cookies and then push the "Save Config" button';
        }
    }


    /**
     * Generate webhook submit button HTML
     *
     * @return string
     * @throws LocalizedException
     */
    public function getWebhookSubmitButtonHtml(): string
    {
        $isWebhookButtonActive = $this->configHelper->getWebhookUrlKeyActive();

        if ($isWebhookButtonActive == 2) {
            $htmlButton = '<form action="#" method="post">
                           <button class="btn btn-light" type="submit" name="button">Activate</button>
                           <a href="mailto:integration@vendor.com">integration@vendor.com</a>
                           </form>';

            $postData = $this->getRequest()->getPost();

            if ($postData) {
                $this->deactivateWebhookButton();
            }

            return $htmlButton;
        }
        return '';
    }

    /**
     * Deactivate Webhook Button
     *
     * @return void
     */
    protected function deactivateWebhookButton(): void
    {
        $objectManager = ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('core_config_data');
        $sql = "UPDATE ".$tableName." SET value = '0' WHERE scope = 'websites' AND path = 'payment/iyzipay/webhook_url_key_active'";
        $connection->query($sql);
    }
}
