<?php
namespace Josequal\APIMobile\Setup;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

class InstallData implements InstallDataInterface
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

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $attributes = [
            'mobile_number' => 'Mobile Number',
            'dial_code'     => 'Dial Code',
            'country_code'  => 'Country Code'
        ];

        foreach ($attributes as $code => $label) {
            if (!$eavSetup->getAttributeId(Customer::ENTITY, $code)) {
                $eavSetup->addAttribute(
                    Customer::ENTITY,
                    $code,
                    [
                        'type'         => 'varchar',
                        'label'        => $label,
                        'input'        => 'text',
                        'required'     => false,
                        'visible'      => true,
                        'user_defined' => true,
                        'position'     => 1000,
                        'system'       => 0,
                        'global'       => ScopedAttributeInterface::SCOPE_GLOBAL
                    ]
                );

                $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $code);
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
