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

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), "1.0.0", "<")) {
            //Your upgrade script
        }
        
        // Security fix: Replace api_key with store_id for multi-store support
        if (version_compare($context->getVersion(), "2.1.5", "<")) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('iyzico_card');
            
            // Add store_id column if it doesn't exist
            if (!$connection->tableColumnExists($tableName, 'store_id')) {
                $connection->addColumn(
                    $tableName,
                    'store_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'unsigned' => true,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Store ID for multi-store support'
                    ]
                );
            }
            
            // Check if api_key column exists before trying to drop it
            if ($connection->tableColumnExists($tableName, 'api_key')) {
                $connection->dropColumn($tableName, 'api_key');
            }
        }
        
        $setup->endSetup();
    }
}
