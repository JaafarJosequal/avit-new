<?php
/**
 * Test file to check if customer_otp table exists
 * Run this file to verify the table was created successfully
 */

namespace Josequal\APIMobile\Test;

use Magento\Framework\App\ObjectManager;

class CheckOtpTable
{
    public static function checkTableExists()
    {
        $objectManager = ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $tableName = $resource->getTableName('customer_otp');

        if ($connection->isTableExists($tableName)) {
            echo "✅ Table 'customer_otp' exists successfully!\n";
            echo "Table name: " . $tableName . "\n";

            // Check table structure
            $columns = $connection->describeTable($tableName);
            echo "Table columns:\n";
            foreach ($columns as $columnName => $columnInfo) {
                echo "  - $columnName: " . $columnInfo['DATA_TYPE'] . "\n";
            }

            return true;
        } else {
            echo "❌ Table 'customer_otp' does not exist!\n";
            echo "Table name: " . $tableName . "\n";
            return false;
        }
    }
}

// Run the check if this file is executed directly
if (php_sapi_name() === 'cli') {
    CheckOtpTable::checkTableExists();
}
