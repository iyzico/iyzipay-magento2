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

namespace Iyzico\Iyzipay\Service;

use Iyzico\Iyzipay\Helper\ConfigHelper;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;

class OneTimeUrlService
{
    private const CACHE_LIFETIME = 1800;
    private const TOKEN_LENGTH = 32;
    private const CACHE_PREFIX = 'iyzipay_goBackUrl_basketId';

    public function __construct(
        protected Random $random,
        protected CacheInterface $cache,
        protected StoreManagerInterface $storeManager,
        protected ConfigHelper $configHelper
    ) {
    }

    /**
     * Generate One Time GoBackURL
     *
     * @param  string  $basketId
     * @return string
     * @throws NoSuchEntityException|LocalizedException
     */
    public function createOneTimeUrl(string $basketId): string
    {
        $token = $this->random->getRandomString(self::TOKEN_LENGTH);
        $this->cache->save(
            $basketId,
            self::CACHE_PREFIX.$token,
            [],
            self::CACHE_LIFETIME
        );

        return $this->configHelper->getGoBackUrl($token);
    }

    /**
     * Validate and Get Basket ID
     *
     * @param  string  $token
     * @return string|null
     */
    public function validateAndGetBasketId(string $token): ?string
    {
        $cacheKey = self::CACHE_PREFIX.$token;
        $basketId = $this->cache->load($cacheKey);

        if ($basketId) {
            $this->cache->remove($cacheKey);
            return $basketId;
        }

        return null;
    }
}
