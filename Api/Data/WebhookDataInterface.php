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

namespace Iyzico\Iyzipay\Api\Data;

interface WebhookDataInterface
{
    /**
     * String constants for property names
     */
    public const PAYMENT_CONVERSATION_ID = "payment_conversation_id";
    public const MERCHANT_ID = "merchant_id";
    public const TOKEN = "token";
    public const STATUS = "status";
    public const IYZI_REFERENCE_CODE = "iyzi_reference_code";
    public const IYZI_EVENT_TYPE = "iyzi_event_type";
    public const IYZI_EVENT_TIME = "iyzi_event_time";
    public const IYZI_PAYMENT_ID = "iyzi_payment_id";

    /**
     * Getter for PaymentConversationId.
     *
     * @return string|null
     */
    public function getPaymentConversationId(): ?string;

    /**
     * Setter for PaymentConversationId.
     *
     * @param  string|null  $paymentConversationId
     *
     * @return void
     */
    public function setPaymentConversationId(?string $paymentConversationId): void;

    /**
     * Getter for MerchantId.
     *
     * @return int|null
     */
    public function getMerchantId(): ?int;

    /**
     * Setter for MerchantId.
     *
     * @param  int|null  $merchantId
     *
     * @return void
     */
    public function setMerchantId(?int $merchantId): void;

    /**
     * Getter for Token.
     *
     * @return string|null
     */
    public function getToken(): ?string;

    /**
     * Setter for Token.
     *
     * @param  string|null  $token
     *
     * @return void
     */
    public function setToken(?string $token): void;

    /**
     * Getter for Status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Setter for Status.
     *
     * @param  string|null  $status
     *
     * @return void
     */
    public function setStatus(?string $status): void;

    /**
     * Getter for IyziReferenceCode.
     *
     * @return string|null
     */
    public function getIyziReferenceCode(): ?string;

    /**
     * Setter for IyziReferenceCode.
     *
     * @param  string|null  $iyziReferenceCode
     *
     * @return void
     */
    public function setIyziReferenceCode(?string $iyziReferenceCode): void;

    /**
     * Getter for IyziEventType.
     *
     * @return string|null
     */
    public function getIyziEventType(): ?string;

    /**
     * Setter for IyziEventType.
     *
     * @param  string|null  $iyziEventType
     *
     * @return void
     */
    public function setIyziEventType(?string $iyziEventType): void;

    /**
     * Getter for IyziEventTime.
     *
     * @return int|null
     */
    public function getIyziEventTime(): ?int;

    /**
     * Setter for IyziEventTime.
     *
     * @param  int|null  $iyziEventTime
     *
     * @return void
     */
    public function setIyziEventTime(?int $iyziEventTime): void;

    /**
     * Getter for IyziPaymentId.
     *
     * @return int|null
     */
    public function getIyziPaymentId(): ?int;

    /**
     * Setter for IyziPaymentId.
     *
     * @param  int|null  $iyziPaymentId
     *
     * @return void
     */
    public function setIyziPaymentId(?int $iyziPaymentId): void;
}
