<?php

namespace Iyzico\Iyzipay\Block\Adminhtml\System\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

class Hidden extends Field
{
    /**
     * Render the hidden field
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setType('hidden');
        return $element->getElementHtml();
    }
}
