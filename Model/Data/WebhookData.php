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

namespace Iyzico\Iyzipay\Model\Data;

use Iyzico\Iyzipay\Api\Data\WebhookDataInterface;
use Magento\Framework\DataObject;

class WebhookData extends DataObject implements WebhookDataInterface
{
    /**
     * Getter for PaymentConversationId.
     *
     * @return string|null
     */
    public function getPaymentConversationId(): ?string
    {
        return $this->getData(self::PAYMENT_CONVERSATION_ID);
    }

    /**
     * Setter for PaymentConversationId.
     *
     * @param  string|null  $paymentConversationId
     *
     * @return void
     */
    public function setPaymentConversationId(?string $paymentConversationId): void
    {
        $this->setData(self::PAYMENT_CONVERSATION_ID, $paymentConversationId);
    }

    /**
     * Getter for MerchantId.
     *
     * @return int|null
     */
    public function getMerchantId(): ?int
    {
        return $this->getData(self::MERCHANT_ID) === null ? null
            : (int) $this->getData(self::MERCHANT_ID);
    }

    /**
     * Setter for MerchantId.
     *
     * @param  int|null  $merchantId
     *
     * @return void
     */
    public function setMerchantId(?int $merchantId): void
    {
        $this->setData(self::MERCHANT_ID, $merchantId);
    }

    /**
     * Getter for Token.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->getData(self::TOKEN);
    }

    /**
     * Setter for Token.
     *
     * @param  string|null  $token
     *
     * @return void
     */
    public function setToken(?string $token): void
    {
        $this->setData(self::TOKEN, $token);
    }

    /**
     * Getter for Status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Setter for Status.
     *
     * @param  string|null  $status
     *
     * @return void
     */
    public function setStatus(?string $status): void
    {
        $this->setData(self::STATUS, $status);
    }

    /**
     * Getter for IyziReferenceCode.
     *
     * @return string|null
     */
    public function getIyziReferenceCode(): ?string
    {
        return $this->getData(self::IYZI_REFERENCE_CODE);
    }

    /**
     * Setter for IyziReferenceCode.
     *
     * @param  string|null  $iyziReferenceCode
     *
     * @return void
     */
    public function setIyziReferenceCode(?string $iyziReferenceCode): void
    {
        $this->setData(self::IYZI_REFERENCE_CODE, $iyziReferenceCode);
    }

    /**
     * Getter for IyziEventType.
     *
     * @return string|null
     */
    public function getIyziEventType(): ?string
    {
        return $this->getData(self::IYZI_EVENT_TYPE);
    }

    /**
     * Setter for IyziEventType.
     *
     * @param  string|null  $iyziEventType
     *
     * @return void
     */
    public function setIyziEventType(?string $iyziEventType): void
    {
        $this->setData(self::IYZI_EVENT_TYPE, $iyziEventType);
    }

    /**
     * Getter for IyziEventTime.
     *
     * @return int|null
     */
    public function getIyziEventTime(): ?int
    {
        return $this->getData(self::IYZI_EVENT_TIME) === null ? null
            : (int) $this->getData(self::IYZI_EVENT_TIME);
    }

    /**
     * Setter for IyziEventTime.
     *
     * @param  int|null  $iyziEventTime
     *
     * @return void
     */
    public function setIyziEventTime(?int $iyziEventTime): void
    {
        $this->setData(self::IYZI_EVENT_TIME, $iyziEventTime);
    }

    /**
     * Getter for IyziPaymentId.
     *
     * @return int|null
     */
    public function getIyziPaymentId(): ?int
    {
        return $this->getData(self::IYZI_PAYMENT_ID) === null ? null
            : (int) $this->getData(self::IYZI_PAYMENT_ID);
    }

    /**
     * Setter for IyziPaymentId.
     *
     * @param  int|null  $iyziPaymentId
     *
     * @return void
     */
    public function setIyziPaymentId(?int $iyziPaymentId): void
    {
        $this->setData(self::IYZI_PAYMENT_ID, $iyziPaymentId);
    }
}
