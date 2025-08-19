<?php
namespace Josequal\APIMobile\Service;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\Webapi\Rest\Request;
use Josequal\APIMobile\Api\Data\ProfileResponseInterface;
use Josequal\APIMobile\Api\Data\ProfileResponseInterfaceFactory;
use Josequal\APIMobile\Api\Data\CustomerLoginDataInterface;
use Josequal\APIMobile\Api\Data\CustomerLoginDataInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Checkout\Model\Cart;

class ProfileService
{
    protected CustomerRepositoryInterface $customerRepository;
    protected CustomerSession $customerSession;
    protected TokenFactory $tokenFactory;
    protected Request $request;
    protected ProfileResponseInterfaceFactory $responseFactory;
    protected CustomerLoginDataInterfaceFactory $customerDataFactory;
    protected CustomerFactory $customerFactory;
    protected AddressRepositoryInterface $addressRepository;
    protected Cart $cart;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        TokenFactory $tokenFactory,
        Request $request,
        ProfileResponseInterfaceFactory $responseFactory,
        CustomerLoginDataInterfaceFactory $customerDataFactory,
        CustomerFactory $customerFactory,
        AddressRepositoryInterface $addressRepository,
        Cart $cart
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->tokenFactory = $tokenFactory;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerFactory = $customerFactory;
        $this->addressRepository = $addressRepository;
        $this->cart = $cart;
    }

    public function getProfile(): ProfileResponseInterface
    {
        try {
            $customerId = $this->validateToken();
            if (!$customerId) {
                return $this->buildResponse(false, 'Invalid or expired token', null, 401);
            }

            $customer = $this->customerRepository->getById($customerId);
            return $this->buildProfileResponse($customer, 'Profile retrieved successfully');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->buildResponse(false, 'User not found', null, 404);
        } catch (\Exception $e) {
            return $this->buildResponse(false, 'Error while retrieving profile', null, 500);
        }
    }

    public function updateProfile(array $data): ProfileResponseInterface
    {
        try {
            $customerId = $this->validateToken();
            if (!$customerId) {
                return $this->buildResponse(false, 'Invalid or expired token', null, 401);
            }

            $customer = $this->customerRepository->getById($customerId);

            // Validate input data before updating
            if (isset($data['firstname']) && !empty($data['firstname'])) {
                if (strlen(trim($data['firstname'])) < 1) {
                    return $this->buildResponse(false, 'First Name is not valid!', null, 400);
                }
            }

            if (isset($data['lastname']) && !empty($data['lastname'])) {
                if (strlen(trim($data['lastname'])) < 1) {
                    return $this->buildResponse(false, 'Last Name is not valid!', null, 400);
                }
            }

            // Update basic information (email is not allowed to be updated)
            if (!empty($data['firstname'])) {
                $customer->setFirstname(trim($data['firstname']));
            }
            if (!empty($data['lastname'])) {
                $customer->setLastname(trim($data['lastname']));
            }
            // Email update is not allowed - remove this section
            // if (!empty($data['email']) && $data['email'] !== $customer->getEmail()) {
            //     $customer->setEmail($data['email']);
            // }

            // Update custom attributes
            if (!empty($data['mobile_number'])) {
                $customer->setCustomAttribute('mobile_number', $data['mobile_number']);
            }
            if (!empty($data['dial_code'])) {
                $customer->setCustomAttribute('dial_code', $data['dial_code']);
            }
            if (!empty($data['country_code'])) {
                $customer->setCustomAttribute('country_code', $data['country_code']);
            }

            $this->customerRepository->save($customer);

            // Also save to customer model and database directly
            try {
                $customerModel = $this->customerFactory->create();
                $customerModel->load($customer->getId());
                $customerModel->setData('mobile_number', $data['mobile_number'] ?? '');
                $customerModel->setData('dial_code', $data['dial_code'] ?? '');
                $customerModel->setData('country_code', $data['country_code'] ?? '');
                $customerModel->save();

                // Save directly to database
                $resourceModel = $customerModel->getResource();
                $connection = $resourceModel->getConnection();
                $tableName = 'customer_entity';

                $connection->update(
                    $tableName,
                    [
                        'mobile_number' => $data['mobile_number'] ?? '',
                        'dial_code' => $data['dial_code'] ?? '',
                        'country_code' => $data['country_code'] ?? ''
                    ],
                    ['entity_id = ?' => $customer->getId()]
                );
            } catch (\Exception $e) {
                // Log error but don't fail the update
                // You can add logger here if needed
            }
            return $this->buildProfileResponse($customer, 'Profile updated successfully');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->buildResponse(false, 'User not found', null, 404);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->buildResponse(false, $e->getMessage(), null, 400);
        } catch (\Exception $e) {
            return $this->buildResponse(false, 'Unexpected error occurred', null, 500);
        }
    }

    private function buildProfileResponse($customer, string $message): ProfileResponseInterface
    {
        $token = $this->getTokenFromHeader();

        // Get billing address
        $billingId = $customer->getDefaultBilling();
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

        // Create CustomerLoginData object using factory
        $customerData = $this->customerDataFactory->create();
        $customerData->setFirstname($customer->getFirstname() ?? '')
            ->setLastname($customer->getLastname() ?? '')
            ->setEmail($customer->getEmail() ?? '')
            ->setToken($token ?? '');

        // Load customer model once for all data retrieval
        $customerModel = $this->customerFactory->create();
        $customerModel->load($customer->getId());

        // Get mobile number - try multiple sources
        $mobileNumber = null;

        // First try custom attribute
        $mobileNumber = $this->getCustomAttributeValue($customer, 'mobile_number');

        // If not found, try customer model data
        if (!$mobileNumber) {
            $mobileNumber = $customerModel->getData('mobile_number');
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

        $customerData->setMobileNumber($mobileNumber ?: '');

        // Get dial code - try multiple sources
        $dialCode = null;

        // First try custom attribute
        $dialCode = $this->getCustomAttributeValue($customer, 'dial_code');

        // If not found, try customer model data
        if (!$dialCode) {
            $dialCode = $customerModel->getData('dial_code');
        }

        $customerData->setDialCode($dialCode ?: '');

        // Get country code - try multiple sources
        $countryCode = null;

        // First try custom attribute
        $countryCode = $this->getCustomAttributeValue($customer, 'country_code');

        // If not found, try customer model data
        if (!$countryCode) {
            $countryCode = $customerModel->getData('country_code');
        }

        $customerData->setCountryCode($countryCode ?: '');

        $customerData->setImage('https://avit.josequal.net/media/default_profile.png')
            ->setAddress($addressString ?: 'No address available')
            ->setPassword(null);

        // Get cart quantity
        try {
            $cartQuantity = (int) $this->cart->getQuote()->getItemsSummaryQty();
            $customerData->setCartQuantity($cartQuantity);
        } catch (\Exception $e) {
            // If cart is not available, set quantity to 0
            $customerData->setCartQuantity(0);
        }

        return $this->buildResponse(true, $message, $customerData, 200);
    }

    private function getCustomAttributeValue($customer, string $attributeCode): ?string
    {
        $attribute = $customer->getCustomAttribute($attributeCode);
        return $attribute ? $attribute->getValue() : null;
    }

    private function validateToken()
    {
        try {
            $token = $this->getTokenFromHeader();
            $customerToken = $this->tokenFactory->create()->loadByToken($token);
            return $customerToken && $customerToken->getCustomerId() ? $customerToken->getCustomerId() : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getTokenFromHeader(): string
    {
        $authHeader = $this->request->getHeader('Authorization');
        return str_replace('Bearer ', '', $authHeader);
    }

    private function buildResponse(bool $status, string $message, CustomerLoginDataInterface $data = null, int $statusCode = 200): ProfileResponseInterface
    {
        $response = $this->responseFactory->create();
        $response->setStatus($status);
        $response->setMessage($message);
        $response->setData($data);
        $response->setStatusCode($statusCode);

        return $response;
    }
}
