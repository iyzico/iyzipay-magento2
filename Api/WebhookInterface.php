<?php

namespace Iyzico\Iyzipay\Api;

/**
 * Interface WebhookInterface
 *
 * @package Iyzico\Iyzipay\Api
 */
interface WebhookInterface
{
  /**
   * Add one number.
   *
   * @param string $webhookUrlKey
   * @return string
   */
  public function getResponse($webhookUrlKey);

}
