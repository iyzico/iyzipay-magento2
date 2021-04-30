<?php

namespace Iyzico\PayWithIyzico\Block\Fronthtml;
use Magento\Framework\View\Element\Template;

class Logo extends Template{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
    }

}
