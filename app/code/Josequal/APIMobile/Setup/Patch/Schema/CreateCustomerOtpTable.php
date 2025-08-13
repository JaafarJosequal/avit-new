<?php
namespace Josequal\APIMobile\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class CreateCustomerOtpTable implements SchemaPatchInterface
{
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $tableName = $this->moduleDataSetup->getTable('customer_otp');

        if (!$this->moduleDataSetup->getConnection()->isTableExists($tableName)) {
            $table = $this->moduleDataSetup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'otp_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                    ],
                    'OTP ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false,
                    ],
                    'Customer ID'
                )
                ->addColumn(
                    'otp',
                    Table::TYPE_VARCHAR,
                    10,
                    [
                        'nullable' => false,
                    ],
                    'OTP Code'
                )
                ->addColumn(
                    'expires_at',
                    Table::TYPE_DATETIME,
                    null,
                    [
                        'nullable' => false,
                    ],
                    'Expiration Date'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addIndex(
                    $this->moduleDataSetup->getIdxName('customer_otp', ['customer_id']),
                    ['customer_id']
                )
                ->addIndex(
                    $this->moduleDataSetup->getIdxName('customer_otp', ['otp']),
                    ['otp']
                )
                ->addIndex(
                    $this->moduleDataSetup->getIdxName('customer_otp', ['expires_at']),
                    ['expires_at']
                )
                ->setComment('Customer OTP Table');

            $this->moduleDataSetup->getConnection()->createTable($table);

            // Add foreign key constraint
            $this->moduleDataSetup->getConnection()->addForeignKey(
                $this->moduleDataSetup->getFkName('customer_otp', 'customer_id', 'customer_entity', 'entity_id'),
                $tableName,
                'customer_id',
                $this->moduleDataSetup->getTable('customer_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            );
        }

        $this->moduleDataSetup->endSetup();
    }
}
