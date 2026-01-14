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

/**
 * Interface IyziInstallmentInterface
 * @package Iyzico\Iyzipay\Api\Data
 */
interface IyziInstallmentInterface
{
    const ID = 'id';
    const BIN_NUMBER = 'bin_number';
    const INSTALLMENT_COUNT = 'installment_count';
    const INSTALLMENT_RATE = 'installment_rate';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get BIN number
     *
     * @return string|null
     */
    public function getBinNumber(): ?string;

    /**
     * Set BIN number
     *
     * @param string $binNumber
     * @return $this
     */
    public function setBinNumber(string $binNumber): IyziInstallmentInterface;

    /**
     * Get installment count
     *
     * @return int|null
     */
    public function getInstallmentCount(): ?int;

    /**
     * Set installment count
     *
     * @param int $count
     * @return $this
     */
    public function setInstallmentCount(int $count): IyziInstallmentInterface;

    /**
     * Get installment rate
     *
     * @return float|null
     */
    public function getInstallmentRate(): ?float;

    /**
     * Set installment rate
     *
     * @param float $rate
     * @return $this
     */
    public function setInstallmentRate(float $rate): IyziInstallmentInterface;

    /**
     * Get status
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): IyziInstallmentInterface;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): IyziInstallmentInterface;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): IyziInstallmentInterface;
}
