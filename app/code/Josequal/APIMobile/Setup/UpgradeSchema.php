<?php
namespace Josequal\APIMobile\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('customer_entity');

            // Add mobile_number column if it doesn't exist
            if (!$connection->tableColumnExists($tableName, 'mobile_number')) {
                $connection->addColumn(
                    $tableName,
                    'mobile_number',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 20,
                        'nullable' => true,
                        'comment' => 'Mobile Number'
                    ]
                );
            }

            // Add dial_code column if it doesn't exist
            if (!$connection->tableColumnExists($tableName, 'dial_code')) {
                $connection->addColumn(
                    $tableName,
                    'dial_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 10,
                        'nullable' => true,
                        'comment' => 'Dial Code'
                    ]
                );
            }

            // Add country_code column if it doesn't exist
            if (!$connection->tableColumnExists($tableName, 'country_code')) {
                $connection->addColumn(
                    $tableName,
                    'country_code',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 10,
                        'nullable' => true,
                        'comment' => 'Country Code'
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
