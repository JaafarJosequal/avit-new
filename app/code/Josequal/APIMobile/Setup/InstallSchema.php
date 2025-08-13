<?php
namespace Josequal\APIMobile\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // ضع هنا أكواد إنشاء الجداول إذا احتجت
        // مثال لإنشاء جدول جديد:
        /*
        $table = $setup->getConnection()
            ->newTable($setup->getTable('custom_table'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            );
        $setup->getConnection()->createTable($table);
        */

        $setup->endSetup();
    }
}
