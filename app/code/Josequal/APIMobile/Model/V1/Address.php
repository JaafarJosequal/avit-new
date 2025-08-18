<?php
namespace Josequal\APIMobile\Model\V1;

class Address extends \Josequal\APIMobile\Model\AbstractModel
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context, $registry, $storeManager, $eventManager);

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->customerSession = $this->objectManager->get('\Magento\Customer\Model\Session');
        $this->addressRepository = $this->objectManager->get('\Magento\Customer\Api\AddressRepositoryInterface');
        $this->addressFactory = $this->objectManager->get('\Magento\Customer\Api\Data\AddressInterfaceFactory');
        $this->regionFactory = $this->objectManager->get('\Magento\Directory\Model\RegionFactory');
    }

    /**
     * Add new address for customer
     */
    public function addAddress($data)
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->errorStatus('Customer not logged in');
            }

            $customerId = $this->customerSession->getCustomerId();

            // Validate required fields
            $requiredFields = ['firstname', 'lastname', 'street', 'city', 'region', 'postcode', 'country_id', 'telephone'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return $this->errorStatus("Field '{$field}' is required");
                }
            }

            // Create address data
            $addressData = [
                'customer_id' => $customerId,
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'street' => is_array($data['street']) ? $data['street'] : [$data['street']],
                'city' => $data['city'],
                'region' => $data['region'],
                'postcode' => $data['postcode'],
                'country_id' => $data['country_id'],
                'telephone' => $data['telephone'],
                'save_in_address_book' => 1
            ];

            // Handle optional fields
            if (isset($data['company'])) {
                $addressData['company'] = $data['company'];
            }
            if (isset($data['fax'])) {
                $addressData['fax'] = $data['fax'];
            }
            if (isset($data['vat_id'])) {
                $addressData['vat_id'] = $data['vat_id'];
            }

            // Handle region_id - always try to get it from region name
            if (!isset($addressData['region_id']) || !$addressData['region_id']) {
                $addressData['region_id'] = $this->getRegionId($addressData['region'], $addressData['country_id']);
            }

            // Create address object
            $address = $this->addressFactory->create();
            $address->setCustomerId($customerId);
            $address->setFirstname($addressData['firstname']);
            $address->setLastname($addressData['lastname']);
            $address->setStreet($addressData['street']);
            $address->setCity($addressData['city']);

            // Set region_id if available, otherwise set region name
            if ($addressData['region_id']) {
                $address->setRegionId($addressData['region_id']);
            } else {
                // Fallback: set region name as string
                $address->setRegion($addressData['region']);
            }

            $address->setPostcode($addressData['postcode']);
            $address->setCountryId($addressData['country_id']);
            $address->setTelephone($addressData['telephone']);
            // Note: setSaveInAddressBook() method doesn't exist in Magento's Address object
            // The address will be saved in address book by default when using addressRepository->save()

            if (isset($addressData['company'])) {
                $address->setCompany($addressData['company']);
            }
            if (isset($addressData['fax'])) {
                $address->setFax($addressData['fax']);
            }
            if (isset($addressData['vat_id'])) {
                $address->setVatId($addressData['vat_id']);
            }

            // Save address
            $savedAddress = $this->addressRepository->save($address);

            return $this->successStatus('Address added successfully', [
                'address_id' => $savedAddress->getId(),
                'message' => 'Address has been added successfully'
            ]);

        } catch (\Exception $e) {
            return $this->errorStatus('Failed to add address: ' . $e->getMessage());
        }
    }

    /**
     * Edit existing address
     */
    public function editAddress($data)
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->errorStatus('Customer not logged in');
            }

            if (!isset($data['address_id']) || empty($data['address_id'])) {
                return $this->errorStatus('Address ID is required');
            }

            $customerId = $this->customerSession->getCustomerId();
            $addressId = $data['address_id'];

            // Load existing address
            try {
                $address = $this->addressRepository->getById($addressId);
            } catch (\Exception $e) {
                return $this->errorStatus('Address not found');
            }

            // Verify address belongs to current customer
            if ($address->getCustomerId() != $customerId) {
                return $this->errorStatus('Address does not belong to current customer');
            }

            // Update fields if provided
            if (isset($data['firstname'])) {
                $address->setFirstname($data['firstname']);
            }
            if (isset($data['lastname'])) {
                $address->setLastname($data['lastname']);
            }
            if (isset($data['street'])) {
                $address->setStreet(is_array($data['street']) ? $data['street'] : [$data['street']]);
            }
            if (isset($data['city'])) {
                $address->setCity($data['city']);
            }
            if (isset($data['region'])) {
                // Get region_id from region name if not provided
                if (!isset($data['region_id']) || !$data['region_id']) {
                    $data['region_id'] = $this->getRegionId($data['region'], $data['country_id'] ?? $address->getCountryId());
                }

                // Set region_id if available, otherwise set region name
                if ($data['region_id']) {
                    $address->setRegionId($data['region_id']);
                } else {
                    // Fallback: set region name as string
                    $address->setRegion($data['region']);
                }
            }
            if (isset($data['postcode'])) {
                $address->setPostcode($data['postcode']);
            }
            if (isset($data['country_id'])) {
                $address->setCountryId($data['country_id']);
            }
            if (isset($data['telephone'])) {
                $address->setTelephone($data['telephone']);
            }
            if (isset($data['company'])) {
                $address->setCompany($data['company']);
            }
            if (isset($data['fax'])) {
                $address->setFax($data['fax']);
            }
            if (isset($data['vat_id'])) {
                $address->setVatId($data['vat_id']);
            }

            // Handle region_id
            if (isset($data['region']) && !isset($data['region_id'])) {
                $address->setRegionId($this->getRegionId($data['region'], $data['country_id'] ?? $address->getCountryId()));
            }

            // Save updated address
            $updatedAddress = $this->addressRepository->save($address);

            return $this->successStatus('Address updated successfully', [
                'address_id' => $updatedAddress->getId(),
                'message' => 'Address has been updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->errorStatus('Failed to update address: ' . $e->getMessage());
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress($data)
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->errorStatus('Customer not logged in');
            }

            if (!isset($data['address_id']) || empty($data['address_id'])) {
                return $this->errorStatus('Address ID is required');
            }

            $customerId = $this->customerSession->getCustomerId();
            $addressId = $data['address_id'];

            // Load existing address
            try {
                $address = $this->addressRepository->getById($addressId);
            } catch (\Exception $e) {
                return $this->errorStatus('Address not found');
            }

            // Verify address belongs to current customer
            if ($address->getCustomerId() != $customerId) {
                return $this->errorStatus('Address does not belong to current customer');
            }

            // Delete address
            $this->addressRepository->deleteById($addressId);

            return $this->successStatus('Address deleted successfully', [
                'message' => 'Address has been deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->errorStatus('Failed to delete address: ' . $e->getMessage());
        }
    }

    /**
     * Get list of customer addresses
     */
    public function getAddresses($data = [])
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                return $this->errorStatus('Customer not logged in');
            }

            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerSession->getCustomer();

            $addresses = [];

            // Get billing address
            if ($customer->getDefaultBilling()) {
                try {
                    $billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
                    $addresses[] = $this->formatAddress($billingAddress, 'billing');
                } catch (\Exception $e) {
                    // Billing address not found, skip
                }
            }

            // Get shipping address
            if ($customer->getDefaultShipping()) {
                try {
                    $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
                    $addresses[] = $this->formatAddress($shippingAddress, 'shipping');
                } catch (\Exception $e) {
                    // Shipping address not found, skip
                }
            }

            // Get all other addresses
            $allAddresses = $customer->getAddresses();
            foreach ($allAddresses as $address) {
                $addressId = $address->getId();
                // Skip if already added as billing or shipping
                if ($addressId != $customer->getDefaultBilling() && $addressId != $customer->getDefaultShipping()) {
                    $addresses[] = $this->formatAddress($address, 'other');
                }
            }

            return $this->successStatus('Addresses retrieved successfully', [
                'addresses' => $addresses,
                'total_count' => count($addresses)
            ]);

        } catch (\Exception $e) {
            return $this->errorStatus('Failed to retrieve addresses: ' . $e->getMessage());
        }
    }

    /**
     * Format address for response
     */
    protected function formatAddress($address, $type)
    {
        return [
            'id' => $address->getId(),
            'type' => $type,
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'company' => $address->getCompany(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'region' => $address->getRegion(),
            'region_id' => $address->getRegionId(),
            'postcode' => $address->getPostcode(),
            'country_id' => $address->getCountryId(),
            'telephone' => $address->getTelephone(),
            'fax' => $address->getFax(),
            'vat_id' => $address->getVatId(),
            'is_default_billing' => $address->getId() == $this->customerSession->getCustomer()->getDefaultBilling(),
            'is_default_shipping' => $address->getId() == $this->customerSession->getCustomer()->getDefaultShipping()
        ];
    }

    /**
     * Get region ID from region name and country ID
     */
    protected function getRegionId($regionName, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            $region->loadByName($regionName, $countryId);

            if ($region->getId()) {
                return $region->getId();
            }

            // If not found by name, try to find by code
            $region->loadByCode($regionName, $countryId);
            if ($region->getId()) {
                return $region->getId();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
