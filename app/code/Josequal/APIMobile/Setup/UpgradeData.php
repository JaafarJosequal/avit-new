<?php
namespace Josequal\APIMobile\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Config as EavConfig;

class UpgradeData implements UpgradeDataInterface
{
    private EavSetupFactory $eavSetupFactory;
    private EavConfig $eavConfig;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        EavConfig $eavConfig
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        // مثال إضافة Attribute جديد عند ترقية الموديول
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            if (!$eavSetup->getAttributeId(Customer::ENTITY, 'example_attr')) {
                $eavSetup->addAttribute(
                    Customer::ENTITY,
                    'example_attr',
                    [
                        'type' => 'varchar',
                        'label' => 'Example Attribute',
                        'input' => 'text',
                        'required' => false,
                        'visible' => true,
                        'user_defined' => true,
                        'system' => 0,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL
                    ]
                );

                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'example_attr');
                $attribute->setData('used_in_forms', [
                    'adminhtml_customer',
                    'customer_account_create',
                    'customer_account_edit'
                ]);
                $attribute->save();
            }
        }

        $setup->endSetup();
    }
}
