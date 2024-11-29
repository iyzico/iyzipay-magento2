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

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\View\Helper\Js;
use Magento\Store\Model\StoreManagerInterface;


/**
 * Class Fieldset
 *
 * This class extends FieldsetInterface and is used to create a custom fieldset for the iyzico payment gateway configuration
 *
 * @package Iyzico\Iyzipay\Block\Adminhtml\Config
 * @extends Fieldset
 *
 * This class is used etc/adminhtml/system.xml
 */
class IyzipayFieldset extends Fieldset
{
    /**
     * StoreManagerInterface
     *
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * IyzipayFieldset constructor
     *
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    public function _getHeaderHtml($element): string
    {
        $html = '';

        if ($element->getIsNested()) {
            $html .= '<tr class="nested"><td colspan="4"><div class="' . $this->_getFrontendClass($element) . '">';
        } else {
            $html .= '<div class="' . $this->_getFrontendClass($element) . '">';
        }

        $html .= '<div class="entry-edit-head admin__collapsible-block">' .
            '<span id="' . $element->getHtmlId() . '-link" class="entry-edit-head-link"></span>';

        $html .= $this->_getHeaderTitleHtml($element);

        $html .= '</div>';
        $html .= '<input id="' . $element->getHtmlId() . '-state" name="config_state[' .
            $element->getId() . ']" type="hidden" value="' . (int) $this->_isCollapseState($element) . '" />';
        $html .= '<fieldset class="config admin__collapsible-block" id="' . $element->getHtmlId() . '" style="position: relative;">';
        $html .= '<legend>' . $element->getLegend() . '</legend>';

        $html .= $this->_getHeaderCommentHtml($element);

        $html .= '<table cellspacing="0" class="form-list"><colgroup class="label" /><colgroup class="value" />';
        if ($this->getRequest()->getParam('website') || $this->getRequest()->getParam('store')) {
            $html .= '<colgroup class="use-default" />';
        }

        $html .= '<colgroup class="scope-label" /><colgroup class="" /><tbody>';
        $html .= '<div style="position:absolute;right: 0px;top:0px;display: flex;flex-direction: column;justify-content: center;">
                    <img src="' . $this->getViewFileUrl('Iyzico_Iyzipay::iyzico/iyzico_logo.png') . '" style="width: 180px; margin-left: auto;" /><span></span>
                </div>';

        return $html;
    }

}
