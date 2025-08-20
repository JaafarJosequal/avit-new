<?php
namespace Josequal\APIMobile\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('customer_account_deletion');

        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `entity_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Entity ID',
            `customer_id` int(11) unsigned NOT NULL COMMENT 'Customer ID',
            `deletion_requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Deletion Requested At',
            `scheduled_deletion_at` timestamp NOT NULL COMMENT 'Scheduled Deletion At',
            `status` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Status: 1=Pending, 2=Cancelled, 3=Completed',
            `reason` text COMMENT 'Deletion Reason',
            `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'Cancelled At',
            `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Actually Deleted At',
            PRIMARY KEY (`entity_id`),
            KEY `CUSTOMER_ACCOUNT_DELETION_CUSTOMER_ID` (`customer_id`),
            KEY `CUSTOMER_ACCOUNT_DELETION_STATUS` (`status`),
            KEY `CUSTOMER_ACCOUNT_DELETION_SCHEDULED_DELETION_AT` (`scheduled_deletion_at`),
            CONSTRAINT `CUSTOMER_ACCOUNT_DELETION_CUSTOMER_ID_CUSTOMER_ENTITY_ENTITY_ID`
                FOREIGN KEY (`customer_id`) REFERENCES `{$setup->getTable('customer_entity')}` (`entity_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Customer Account Deletion Requests';";

        $setup->getConnection()->query($sql);

        $setup->endSetup();
    }
}
