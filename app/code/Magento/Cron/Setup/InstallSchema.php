<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Cron\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'cron_schedule'
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('cron_schedule')
        )->addColumn(
            'schedule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Schedule Id'
        )->addColumn(
            'job_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'default' => '0'],
            'Job Code'
        )->addColumn(
            'status',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            7,
            ['nullable' => false, 'default' => 'pending'],
            'Status'
        )->addColumn(
            'messages',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Messages'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'scheduled_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Scheduled At'
        )->addColumn(
            'executed_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Executed At'
        )->addColumn(
            'finished_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Finished At'
        )->addIndex(
            $installer->getIdxName('cron_schedule', ['job_code']),
            ['job_code']
        )->addIndex(
            $installer->getIdxName('cron_schedule', ['scheduled_at', 'status']),
            ['scheduled_at', 'status']
        )->addIndex(
            $installer->getIdxName('cron_schedule', ['status', 'job_code']),
            ['status', 'job_code']
        )->setComment(
            'Cron Schedule'
        );
        $installer->getConnection()->createTable($table);

        /**
         * Create table 'cron_schedule_jobcode_lock'
         * We use this for locking job_codes from cron_schedule without creating deadlocks.
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('cron_schedule_jobcode_lock')
        )->addColumn(
            'job_code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false, 'primary' => true],
            'Job Code'
        )->addColumn(
            'schedule_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Schedule Id'
        )->addColumn(
            'executed_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => true],
            'Executed At'
        )->addIndex(
            $installer->getIdxName('cron_schedule_jobcode_lock', ['schedule_id']),
            ['schedule_id']
        )->setComment(
            'Cron Schedule Jobcode Lock'
        );
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
