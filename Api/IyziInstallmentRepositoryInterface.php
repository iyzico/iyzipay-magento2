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

namespace Iyzico\Iyzipay\Api;

use Iyzico\Iyzipay\Api\Data\IyziInstallmentInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface IyziInstallmentRepositoryInterface
 * @package Iyzico\Iyzipay\Api
 */
interface IyziInstallmentRepositoryInterface
{
    /**
     * Save installment
     *
     * @param IyziInstallmentInterface $installment
     * @return IyziInstallmentInterface
     * @throws CouldNotSaveException
     */
    public function save(IyziInstallmentInterface $installment): IyziInstallmentInterface;

    /**
     * Get installment by ID
     *
     * @param int $id
     * @return IyziInstallmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): IyziInstallmentInterface;

    /**
     * Get list of installments
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Iyzico\Iyzipay\Api\Data\IyziInstallmentSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete installment
     *
     * @param IyziInstallmentInterface $installment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(IyziInstallmentInterface $installment): bool;

    /**
     * Delete installment by ID
     *
     * @param int $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool;
}
