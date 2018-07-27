<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Cron\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $installer = $setup;
            $installer->startSetup();

            $installer->getConnection()->addIndex(
                $installer->getTable('cron_schedule'),
                $installer->getIdxName('cron_schedule', ['status', 'job_code']),
                ['status', 'job_code']
            );

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
}
