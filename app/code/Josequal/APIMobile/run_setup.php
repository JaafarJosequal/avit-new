<?php
/**
 * Manual setup script to create customer attributes
 * Run this file from Magento root directory
 */

require_once 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

try {
    // Get setup
    $setup = $objectManager->create(\Magento\Framework\Setup\ModuleDataSetupInterface::class);
    $eavSetupFactory = $objectManager->create(\Magento\Eav\Setup\EavSetupFactory::class);
    $eavSetup = $eavSetupFactory->create(['setup' => $setup]);

    echo "Starting setup...\n";

    // Add mobile_number attribute
    if (!$eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'mobile_number')) {
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'mobile_number',
            [
                'type' => 'varchar',
                'label' => 'Mobile Number',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'position' => 999,
                'system' => 0,
            ]
        );
        echo "Added mobile_number attribute\n";
    } else {
        echo "mobile_number attribute already exists\n";
    }

    // Add dial_code attribute
    if (!$eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'dial_code')) {
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'dial_code',
            [
                'type' => 'varchar',
                'label' => 'Dial Code',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'position' => 1000,
                'system' => 0,
            ]
        );
        echo "Added dial_code attribute\n";
    } else {
        echo "dial_code attribute already exists\n";
    }

    // Add country_code attribute
    if (!$eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'country_code')) {
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'country_code',
            [
                'type' => 'varchar',
                'label' => 'Country Code',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'position' => 1001,
                'system' => 0,
            ]
        );
        echo "Added country_code attribute\n";
    } else {
        echo "country_code attribute already exists\n";
    }

    echo "Setup completed successfully!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
