<?php
namespace Josequal\ImportExport\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()
            ->newTable($installer->getTable('josequal_import_export_log'))
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )
            ->addColumn(
                'operation_type',
                Table::TYPE_TEXT,
                20,
                ['nullable' => false],
                'Operation Type'
            )
            ->addColumn(
                'entity_type',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Entity Type'
            )
            ->addColumn(
                'file_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'File Name'
            )
            ->addColumn(
                'total_records',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Total Records'
            )
            ->addColumn(
                'successful_records',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Successful Records'
            )
            ->addColumn(
                'failed_records',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'Failed Records'
            )
            ->addColumn(
                'error_log',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Error Log'
            )
            ->addColumn(
                'started_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Started At'
            )
            ->addColumn(
                'completed_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Completed At'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => 1],
                'Status'
            )
            ->addColumn(
                'admin_user_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true],
                'Admin User ID'
            )
            ->setComment('Import Export Log');

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
