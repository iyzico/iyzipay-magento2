<?php
namespace Iyzico\PayWithIyzico\Block\Adminhtml\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Model\Auth\Session;


class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    private $storeManager;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    public function _getHeaderHtml($element)
    {
        if ($element->getIsNested()) {
            $html = '<tr class="nested"><td colspan="4"><div class="' . $this->_getFrontendClass($element) . '">';
        } else {
            $html = '<div class="' . $this->_getFrontendClass($element) . '">';
        }

        $html .= '<div class="entry-edit-head admin__collapsible-block">' .
            '<span id="' .
            $element->getHtmlId() .
            '-link" class="entry-edit-head-link"></span>';

        $html .= $this->_getHeaderTitleHtml($element);

        $html .= '</div>';
        $html .= '<input id="' .
            $element->getHtmlId() .
            '-state" name="config_state[' .
            $element->getId() .
            ']" type="hidden" value="' .
            (int)$this->_isCollapseState(
                $element
            ) . '" />';
        $html .= '<fieldset class="config admin__collapsible-block" id="' . $element->getHtmlId() . '" style="position: relative;">';
        $html .= '<legend>' . $element->getLegend().'</legend>';

        $html .= $this->_getHeaderCommentHtml($element);

        // field label column
        $html .= '<table cellspacing="0" class="form-list"><colgroup class="label" /><colgroup class="value" />';
        if ($this->getRequest()->getParam('website') || $this->getRequest()->getParam('store')) {
            $html .= '<colgroup class="use-default" />';
        }
        $html .= '<colgroup class="scope-label" /><colgroup class="" /><tbody>';
        $html .= '<div style="position:absolute;right: 0px;top:0px;display: flex;flex-direction: column;justify-content: center;">
                    <img src="'.$this->getViewFileUrl('Iyzico_PayWithIyzico::iyzico/paywithiyzico-logo.png').'" style="    width: 150px;
    margin-left: auto;" />
                </div>';
        return $html;
    }

}
