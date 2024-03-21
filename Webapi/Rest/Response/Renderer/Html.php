<?php

namespace Iyzico\Iyzipay\Webapi\Rest\Response\Renderer;

use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Response\RendererInterface;

/**
 * Class Html
 *
 * @package Iyzico\Iyzipay\Webapi\Rest\Response\Renderer
 */
class Html implements RendererInterface
{
    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return 'text/html';
    }

    /**
     * @param  array|bool|float|int|object|string|null $data
     * @return string
     * @throws Exception
     */
    public function render($data): string
    {
        if (is_string($data)) {
            return $data;
        } else {
            throw new Exception(
                __('Data is not html.')
            );
        }
    }
}
