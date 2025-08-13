<?php
namespace Josequal\APIMobile\Service;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Model\Session;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Josequal\APIMobile\Api\Data\AuthResponseInterface;
use Josequal\APIMobile\Model\Data\AuthResponse;
use Josequal\APIMobile\Model\Data\CustomerLoginData;
use Magento\Framework\Webapi\Exception as WebapiException;


class AuthService
{
    protected CustomerFactory $customerFactory;
    protected CustomerRepositoryInterface $customerRepository;
    protected AccountManagementInterface $accountManagement;
    protected CustomerInterfaceFactory $customerDataFactory;
    protected EncryptorInterface $encryptor;
    protected Session $session;
    protected CustomerTokenServiceInterface $customerTokenService;
    protected AddressRepositoryInterface $addressRepository;
    protected AddressInterfaceFactory $addressFactory;
    protected AttributeValueFactory $attributeValueFactory;

    public function __construct(
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        CustomerInterfaceFactory $customerDataFactory,
        EncryptorInterface $encryptor,
        Session $session,
        CustomerTokenServiceInterface $customerTokenService,
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressFactory,
        AttributeValueFactory $attributeValueFactory
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
        $this->customerDataFactory = $customerDataFactory;
        $this->encryptor = $encryptor;
        $this->session = $session;
        $this->customerTokenService = $customerTokenService;
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->attributeValueFactory = $attributeValueFactory;
    }

public function register(array $data): AuthResponseInterface
{
    $response = new AuthResponse();

    $requiredFields = [
        'firstname', 'lastname', 'email', 'password',
        'mobile_number', 'dial_code', 'country_code'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            throw new WebapiException(__("Missing required field: $field"), 400);
        }
    }

    try {
        $existingCustomer = $this->customerRepository->get($data['email']);
        if ($existingCustomer && $existingCustomer->getId()) {
            throw new WebapiException(__('Email already registered'), 400);
        }
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        // No customer exists, continue
    }

    try {
        $customer = $this->customerDataFactory->create();
        $customer->setFirstname($data['firstname']);
        $customer->setLastname($data['lastname']);
        $customer->setEmail($data['email']);

        $createdCustomer = $this->accountManagement->createAccount($customer, $data['password']);
        $createdCustomer->setConfirmation(null);

        // Save custom attributes directly to the customer
        if (isset($data['mobile_number'])) {
            $createdCustomer->setCustomAttribute('mobile_number', $data['mobile_number']);
        }
        if (isset($data['dial_code'])) {
            $createdCustomer->setCustomAttribute('dial_code', $data['dial_code']);
        }
        if (isset($data['country_code'])) {
            $createdCustomer->setCustomAttribute('country_code', $data['country_code']);
        }

        // Save the customer to persist the custom attributes
        $this->customerRepository->save($createdCustomer);

        // Also save to customer model for backup and ensure data is saved
        $customerModel = $this->customerFactory->create();
        $customerModel->load($createdCustomer->getId());
        $customerModel->setData('mobile_number', $data['mobile_number']);
        $customerModel->setData('dial_code', $data['dial_code']);
        $customerModel->setData('country_code', $data['country_code']);
        $customerModel->save();

        // Reload the customer to ensure we have the latest data
        $savedCustomer = $this->customerRepository->getById($createdCustomer->getId());

        // Get billing address for the new customer
        $billingId = $savedCustomer->getDefaultBilling();
        $billingAddress = null;
        $addressString = null;

        if ($billingId) {
            try {
                $billingAddress = $this->addressRepository->getById($billingId);
                // Build address string
                $addressParts = [];
                if ($billingAddress->getStreet()) {
                    $addressParts[] = implode(', ', $billingAddress->getStreet());
                }
                if ($billingAddress->getCity()) {
                    $addressParts[] = $billingAddress->getCity();
                }
                if ($billingAddress->getRegion()) {
                    $addressParts[] = $billingAddress->getRegion()->getRegion();
                }
                if ($billingAddress->getPostcode()) {
                    $addressParts[] = $billingAddress->getPostcode();
                }
                if ($billingAddress->getCountryId()) {
                    $addressParts[] = $billingAddress->getCountryId();
                }
                $addressString = implode(', ', $addressParts);
            } catch (\Exception $e) {
                $addressString = null;
            }
        }

        // Prepare login data object
        $loginData = new CustomerLoginData();
        $loginData->setFirstname($savedCustomer->getFirstname());
        $loginData->setLastname($savedCustomer->getLastname());
        $loginData->setEmail($savedCustomer->getEmail());

        // Get real saved data from customer model
        $customerModel = $this->customerFactory->create();
        $customerModel->load($savedCustomer->getId());

        // Get phone data using helper method
        $phoneData = $this->getCustomerPhoneData($savedCustomer, $customerModel);

        // Set the real values
        $loginData->setMobileNumber($phoneData['mobile_number']);
        $loginData->setDialCode($phoneData['dial_code']);
        $loginData->setCountryCode($phoneData['country_code']);

        $loginData->setImage('https://yourdomain.com/media/default_profile.png'); // Default profile image

        // Generate token for the newly created user
        try {
            $token = $this->customerTokenService->createCustomerAccessToken($data['email'], $data['password']);
            $loginData->setToken($token);
        } catch (\Exception $e) {
            $loginData->setToken('');
        }

        $loginData->setPassword(null);

        $loginData->setAddress($addressString ?: 'No address available'); // Add address with default value

        return $response->setStatus(true)
            ->setMessage('Account created and activated successfully.')
            ->setData($loginData)
            ->setStatusCode(200);

    } catch (LocalizedException $e) {
        throw new WebapiException(__($e->getMessage()), 400);
    }
}

public function login(string $email, string $password): AuthResponseInterface
{
    $response = new AuthResponse();
    try {
        $customer = $this->accountManagement->authenticate($email, $password);
        $this->session->setCustomerDataAsLoggedIn($customer);

        $token = $this->customerTokenService->createCustomerAccessToken($email, $password);
        $fullCustomer = $this->customerRepository->getById($customer->getId());

        $billingId = $fullCustomer->getDefaultBilling();
        $billingAddress = null;
        $addressString = null;

        if ($billingId) {
            try {
                $billingAddress = $this->addressRepository->getById($billingId);
                // Build address string
                $addressParts = [];
                if ($billingAddress->getStreet()) {
                    $addressParts[] = implode(', ', $billingAddress->getStreet());
                }
                if ($billingAddress->getCity()) {
                    $addressParts[] = $billingAddress->getCity();
                }
                if ($billingAddress->getRegion()) {
                    $addressParts[] = $billingAddress->getRegion()->getRegion();
                }
                if ($billingAddress->getPostcode()) {
                    $addressParts[] = $billingAddress->getPostcode();
                }
                if ($billingAddress->getCountryId()) {
                    $addressParts[] = $billingAddress->getCountryId();
                }
                $addressString = implode(', ', $addressParts);
            } catch (\Exception $e) {
                $addressString = null;
            }
        }

        // Create login data object
        $loginData = new \Josequal\APIMobile\Model\Data\CustomerLoginData();
        $loginData->setFirstname($fullCustomer->getFirstname());
        $loginData->setLastname($fullCustomer->getLastname());
        $loginData->setEmail($fullCustomer->getEmail());

        // Load customer model to get all data
        $customerModel = $this->customerFactory->create();
        $customerModel->load($fullCustomer->getId());

        // Get phone data using helper method
        $phoneData = $this->getCustomerPhoneData($fullCustomer, $customerModel);

        // Set the values
        $loginData->setMobileNumber($phoneData['mobile_number']);
        $loginData->setDialCode($phoneData['dial_code']);
        $loginData->setCountryCode($phoneData['country_code']);

        $loginData->setToken($token);
        $loginData->setPassword(null);
        $loginData->setImage('https://yourdomain.com/media/default_profile.png');
        $loginData->setAddress($addressString ?: 'No address available');

        return $response->setStatus(true)
            ->setMessage(__('Logged in'))
            ->setData($loginData)
            ->setStatusCode(200);

    } catch (LocalizedException $e) {
        throw new WebapiException(__($e->getMessage()), 401);
    }
}

    public function logout(): AuthResponseInterface
    {
        $response = new AuthResponse();
        $this->session->logout();
        $loginData = new CustomerLoginData();
        $loginData->setAddress(null); // Set address to null as requested

        return $response->setStatus(true)
            ->setMessage(__('Logged out'))
            ->setData($loginData)
            ->setStatusCode(200);
    }

    private function getCustomerPhoneData($customer, $customerModel) {
        $mobileNumber = null;
        $dialCode = null;
        $countryCode = null;

        // Try to get from customer model first (direct database access)
        $mobileNumber = $customerModel->getData('mobile_number');
        $dialCode = $customerModel->getData('dial_code');
        $countryCode = $customerModel->getData('country_code');

        // If not found in customer model, try custom attributes
        if (!$mobileNumber) {
            $mobileNumberAttr = $customer->getCustomAttribute('mobile_number');
            if ($mobileNumberAttr && $mobileNumberAttr->getValue()) {
                $mobileNumber = $mobileNumberAttr->getValue();
            }
        }

        if (!$dialCode) {
            $dialCodeAttr = $customer->getCustomAttribute('dial_code');
            if ($dialCodeAttr && $dialCodeAttr->getValue()) {
                $dialCode = $dialCodeAttr->getValue();
            }
        }

        if (!$countryCode) {
            $countryCodeAttr = $customer->getCustomAttribute('country_code');
            if ($countryCodeAttr && $countryCodeAttr->getValue()) {
                $countryCode = $countryCodeAttr->getValue();
            }
        }

        // If still not found, try addresses
        if (!$mobileNumber) {
            $addresses = $customer->getAddresses();
            if ($addresses) {
                foreach ($addresses as $address) {
                    if ($address->getTelephone()) {
                        $mobileNumber = $address->getTelephone();
                        break;
                    }
                }
            }
        }

        return [
            'mobile_number' => $mobileNumber ?: '',
            'dial_code' => $dialCode ?: '',
            'country_code' => $countryCode ?: ''
        ];
    }

    private function saveCustomerPhoneData($customerId, $data) {
        try {
            // Load customer model
            $customerModel = $this->customerFactory->create();
            $customerModel->load($customerId);

            // Save to customer model
            $customerModel->setData('mobile_number', $data['mobile_number'] ?? '');
            $customerModel->setData('dial_code', $data['dial_code'] ?? '');
            $customerModel->setData('country_code', $data['country_code'] ?? '');
            $customerModel->save();

            // Also save to customer repository
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('mobile_number', $data['mobile_number'] ?? '');
            $customer->setCustomAttribute('dial_code', $data['dial_code'] ?? '');
            $customer->setCustomAttribute('country_code', $data['country_code'] ?? '');
            $this->customerRepository->save($customer);

            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the registration
            return false;
        }
    }
}
