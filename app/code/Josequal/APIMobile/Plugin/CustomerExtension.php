<?php
namespace Josequal\APIMobile\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerExtensionFactory;

class CustomerExtension
{
    private CustomerExtensionFactory $extensionFactory;

    public function __construct(CustomerExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    public function afterGetById(
        \Magento\Customer\Api\CustomerRepositoryInterface $subject,
        CustomerInterface $result
    ) {
        $extensionAttributes = $result->getExtensionAttributes();

        if (!$extensionAttributes) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        // Load custom attributes manually
        $customer = $result;
        $dialCode = $customer->getCustomAttribute('dial_code')?->getValue();
        $countryCode = $customer->getCustomAttribute('country_code')?->getValue();

        $extensionAttributes->setDialCode($dialCode);
        $extensionAttributes->setCountryCode($countryCode);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}
