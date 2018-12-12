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

use \Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        $installer = $setup;

        if ($installer->tableExists('iyzico_order') && $installer->tableExists('iyzico_card') ) {
            return;
        }

        $installer->startSetup();

        /**
         * Create table 'iyzico_order'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('iyzico_order'))
            ->addColumn(
                'iyzico_order_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true,
                ],
                'iyzico Order Id'
            )
            ->addColumn(
                'payment_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                ],
                'iyzico Payment Id'
            )
            ->addColumn(
                'order_id',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => false,
                ],
                'iyzico Order Id'
            )
            ->addColumn(
                'total_amount',
                Table::TYPE_DECIMAL,
                '10,2',
                [
                    'nullable' => false,
                ],
                'iyzico Total Amount'
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ],
                'Order Id'
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => false,
                ],
                'iyzico Status'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false, 
                    'default' => Table::TIMESTAMP_INIT,
                ],
                'iyzico Card - Created At'
            )
            ->setComment('iyzico Order');
        $installer->getConnection()->createTable($table);


         /**
         * Create table 'iyzico_card'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('iyzico_card'))
            ->addColumn(
                'iyzico_card_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true,
                ],
                'iyzico Card - Magento Card Id'
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                ],
                'iyzico Card - Magento Customer Id'
            )
            ->addColumn(
                'card_user_key',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => false,
                ],
                'iyzico Card - Card User Key'
            )
            ->addColumn(
                'api_key',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => false,
                ],
                'iyzico Card - Api Key'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false, 
                    'default' => Table::TIMESTAMP_INIT,
                ],
                'iyzico Card - Created At'
            )
            ->setComment('iyzico Order');
        $installer->getConnection()->createTable($table);



        $feeOptions = [
            'type' =>  Table::TYPE_DECIMAL,
            'length' => '10,2',
            'visible' => false,
            'required' => false,
            'comment' => 'Installment Fee'
        ];

        $feeCountOptions = [
            'type' =>  Table::TYPE_INTEGER,
            'visible' => false,
            'required' => false,
            'comment' => 'Installment Count'
        ];

        $installer->getConnection()->addColumn($installer->getTable("sales_order"), "installment_fee", $feeOptions);
        $installer->getConnection()->addColumn($installer->getTable("quote"), "installment_fee", $feeOptions);
        $installer->getConnection()->addColumn($installer->getTable("sales_order"), "installment_count", $feeCountOptions);
        $installer->getConnection()->addColumn($installer->getTable("quote"), "installment_count", $feeCountOptions);

        $installer->endSetup();
    }
}
