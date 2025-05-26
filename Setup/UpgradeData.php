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

namespace Iyzico\Iyzipay\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), "1.0.0", "<")) {
            //Your upgrade script
        }
        
        // Security fix: Clean up any existing API key data before column removal
        if (version_compare($context->getVersion(), "2.1.5", "<")) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('iyzico_card');
            
            // Check if table and api_key column exist
            if ($connection->isTableExists($tableName) && $connection->tableColumnExists($tableName, 'api_key')) {
                // Clear all api_key data for security
                $connection->update($tableName, ['api_key' => ''], '1=1');
            }
        }
        
        $setup->endSetup();
    }
}
