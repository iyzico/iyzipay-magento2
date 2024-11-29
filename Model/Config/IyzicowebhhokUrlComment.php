<?php

namespace Iyzico\Iyzipay\Model\Config;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Phrase;

/**
 * Class IyzicowebhhokUrlComment
 *
 * @package Iyzico\Iyzipay\Model\Config
 */
class IyzicowebhhokUrlComment implements CommentInterface
{

    /**
     * @param  string $elementValue
     * @return Phrase|string
     */
    public function getCommentText($elementValue)
    {
        return __("Don't forget to do webhook Integration.");
    }
}
