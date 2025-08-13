<?php
namespace Josequal\APIMobile\Model\V1;

use Josequal\APIMobile\Api\V1\PasswordInterface;
use Josequal\APIMobile\Api\Data\PasswordResponseInterface;
use Josequal\APIMobile\Model\Data\PasswordResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Josequal\APIMobile\Model\ResourceModel\Otp as OtpResourceModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Josequal\APIMobile\Model\V1\OtpFactory;

class Password implements PasswordInterface
{
    protected $customerAccountManagement;
    protected $customerRepository;
    protected $tokenFactory;
    protected $request;
    protected $logger;
    protected $otpFactory;
    protected $customerFactory;
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $storeManager;
    protected $cache;
    private $accountManagement;
    protected $customerResourceModel;
    protected $otpResourceModel;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $filterGroupBuilder;
    protected $customerSession;

    public function __construct(
        AccountManagementInterface $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        TokenFactory $tokenFactory,
        OtpFactory $otpFactory,
        Request $request,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\CacheInterface $cache,
        LoggerInterface $logger,
        \Magento\Customer\Api\AccountManagementInterface $accountManagement,
        CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        OtpResourceModel $otpResourceModel,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->tokenFactory = $tokenFactory;
        $this->request = $request;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cache = $cache;
        $this->accountManagement = $accountManagement;
        $this->otpFactory = $otpFactory;
        $this->logger = $logger;
        $this->customerFactory = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->otpResourceModel = $otpResourceModel;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->customerSession = $customerSession;
    }

    protected function getMinimumPasswordLength()
    {
        return 9;
    }

    private function validateToken()
    {
        try {
            // First check if customer is logged in via session
            if ($this->customerSession && $this->customerSession->isLoggedIn()) {
                return $this->customerSession->getCustomerId();
            }

            // If not logged in via session, check for Bearer token
            $token = $this->request->getHeader('Authorization');

            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            } else {
                return false;
            }

            $tokenModel = $this->tokenFactory->create()->loadByToken($token);
            if (!$tokenModel->getId() || $tokenModel->getRevoked()) {
                return false;
            }

            return $tokenModel->getCustomerId();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getOtpExpirationTime()
    {
        return 15; // minutes
    }

    public function updatePassword(string $oldPassword, string $newPassword, string $confirmPassword): PasswordResponseInterface
    {
        $response = new PasswordResponse();

        try {
            $customerId = $this->validateToken();

            if (!$customerId) {
                return $response->setStatus(false)
                    ->setMessage('Invalid or expired token')
                    ->setData([])
                    ->setStatusCode(401);
            }

            $customer = $this->customerRepository->getById($customerId);
            $email = $customer->getEmail();

            if (!$this->customerAccountManagement->authenticate($email, $oldPassword)) {
                return $response->setStatus(false)
                    ->setMessage('Incorrect old password')
                    ->setData([])
                    ->setStatusCode(401);
            }

            if ($newPassword !== $confirmPassword) {
                return $response->setStatus(false)
                    ->setMessage('New password and confirm password do not match')
                    ->setData([])
                    ->setStatusCode(400);
            }

            if (strlen($newPassword) < $this->getMinimumPasswordLength()) {
                return $response->setStatus(false)
                    ->setMessage('Password must be at least ' . $this->getMinimumPasswordLength() . ' characters long')
                    ->setData([])
                    ->setStatusCode(400);
            }

            $this->customerAccountManagement->changePassword($email, $oldPassword, $newPassword);

            return $response->setStatus(true)
                ->setMessage('Password updated successfully')
                ->setData([])
                ->setStatusCode(200);

        } catch (\Magento\Framework\Exception\InvalidEmailOrPasswordException $e) {
            return $response->setStatus(false)
                ->setMessage('Incorrect credentials')
                ->setData([])
                ->setStatusCode(401);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $response->setStatus(false)
                ->setMessage($e->getMessage())
                ->setData([])
                ->setStatusCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Error updating password: ' . $e->getMessage());
            return $response->setStatus(false)
                ->setMessage('An error occurred while updating password')
                ->setData([])
                ->setStatusCode(500);
        }
    }

    public function forgetPassword(string $emailOrPhone, string $channel): PasswordResponseInterface
    {
        $response = new PasswordResponse();

        try {
            $customer = null;

            // Check if the channel is email
            if ($channel == 'email') {
                try {
                    $customer = $this->customerRepository->get($emailOrPhone);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    return $response->setStatus(false)
                        ->setMessage('Customer not found')
                        ->setData([])
                        ->setStatusCode(404);
                }
            } elseif ($channel == 'phone') {
                // Check if the channel is phone and fetch customer by mobile_number attribute
                $filter = $this->filterBuilder
                    ->setField('mobile_number')
                    ->setValue($emailOrPhone)
                    ->setConditionType('eq')
                    ->create();

                $filterGroup = $this->filterGroupBuilder
                    ->addFilter($filter)
                    ->create();

                $searchCriteria = $this->searchCriteriaBuilder
                    ->setFilterGroups([$filterGroup])
                    ->create();

                $customerList = $this->customerRepository->getList($searchCriteria);
                $customers = $customerList->getItems();

                if (empty($customers)) {
                    // If not found by mobile_number, try searching by telephone in addresses
                    $customer = $this->findCustomerByPhoneInAddresses($emailOrPhone);
                    if (!$customer) {
                        return $response->setStatus(false)
                            ->setMessage('Customer not found with this phone number')
                            ->setData([])
                            ->setStatusCode(404);
                    }
                } else {
                    $customer = reset($customers); // Get the first customer
                }
            } else {
                return $response->setStatus(false)
                    ->setMessage('Invalid channel. Use "email" or "phone"')
                    ->setData([])
                    ->setStatusCode(400);
            }

            if (!$customer || !$customer->getId()) {
                return $response->setStatus(false)
                    ->setMessage('Customer not found')
                    ->setData([])
                    ->setStatusCode(404);
            }

            // Generate OTP
            $otp = $this->generateOTP();

            // Set OTP expiration time
            $customerId = $customer->getId();
            $expiresAt = (new \DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');

            // Save OTP to database
            $this->otpFactory->create()
                ->setCustomerId($customerId)
                ->setOtp($otp)
                ->setExpiresAt($expiresAt)
                ->save();

            // Send OTP based on the channel (email or phone)
            if ($channel == 'email') {
                // Send OTP via email
                $this->sendOtpEmail($customer->getEmail(), $otp);
            } elseif ($channel == 'phone') {
                // Send OTP via phone (SMS)
                $phoneNumber = $this->getCustomerPhoneNumber($customer);
                if ($phoneNumber) {
                    $this->sendOtpSms($phoneNumber, $otp);
                } else {
                    $this->logger->error('No mobile number found for customer ID: ' . $customer->getId());
                    return $response->setStatus(false)
                        ->setMessage('No mobile number found for this customer')
                        ->setData([])
                        ->setStatusCode(400);
                }
            }

            return $response->setStatus(true)
                ->setMessage('OTP sent successfully.')
                ->setData(['otp' => $otp])
                ->setStatusCode(200);

        } catch (\Exception $e) {
            $this->logger->error('Error in forgetPassword: ' . $e->getMessage());
            return $response->setStatus(false)
                ->setMessage('Unable to send OTP.')
                ->setData([])
                ->setStatusCode(500);
        }
    }

    /**
     * Find customer by phone number in addresses
     *
     * @param string $phoneNumber
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function findCustomerByPhoneInAddresses(string $phoneNumber)
    {
        try {
            // Load customer model to search in addresses
            $customerModel = $this->customerFactory->create();
            $customerCollection = $customerModel->getCollection();

            // Get all customers and check their addresses
            foreach ($customerCollection as $customer) {
                $addresses = $customer->getAddresses();
                if ($addresses) {
                    foreach ($addresses as $address) {
                        if ($address->getTelephone() === $phoneNumber) {
                            return $this->customerRepository->getById($customer->getId());
                        }
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Error finding customer by phone in addresses: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get customer phone number from various sources
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return string|null
     */
    private function getCustomerPhoneNumber($customer)
    {
        // First try to get mobile_number custom attribute
        $mobileNumber = $this->getCustomAttributeValue($customer, 'mobile_number');
        if ($mobileNumber) {
            return $mobileNumber;
        }

        // If not found, try to get from customer model directly
        try {
            $customerModel = $this->customerFactory->create();
            $customerModel->load($customer->getId());
            $mobileNumber = $customerModel->getData('mobile_number');
            if ($mobileNumber) {
                return $mobileNumber;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error loading customer model: ' . $e->getMessage());
        }

        // If still not found, try to get from addresses
        try {
            $addresses = $customer->getAddresses();
            if ($addresses) {
                foreach ($addresses as $address) {
                    if ($address->getTelephone()) {
                        return $address->getTelephone();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getting customer addresses: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get custom attribute value
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $attributeCode
     * @return string|null
     */
    private function getCustomAttributeValue($customer, string $attributeCode): ?string
    {
        $attribute = $customer->getCustomAttribute($attributeCode);
        return $attribute ? $attribute->getValue() : null;
    }

    public function verifyOtp(string $otp): PasswordResponseInterface
    {
        $response = new PasswordResponse();

        try {
            // Find OTP in database without requiring authentication
            $storedOtp = $this->otpFactory->create()
                ->getCollection()
                ->addFieldToFilter('otp', $otp)
                ->addFieldToFilter('expires_at', ['gteq' => (new \DateTime())->format('Y-m-d H:i:s')])
                ->getFirstItem();

            if (!$storedOtp->getId()) {
                return $response->setStatus(false)
                    ->setMessage('OTP expired or not found')
                    ->setData([])
                    ->setStatusCode(401);
            }

            // OTP is valid, return success with customer ID for next step
            return $response->setStatus(true)
                ->setMessage('OTP verified successfully')
                ->setData([
                    'verified' => true,
                    'customer_id' => $storedOtp->getCustomerId()
                ])
                ->setStatusCode(200);

        } catch (\Exception $e) {
            $this->logger->error('Error in verifyOtp: ' . $e->getMessage());
            return $response->setStatus(false)
                ->setMessage('Error verifying OTP')
                ->setData([])
                ->setStatusCode(500);
        }
    }

    public function resetPassword(string $newPassword, string $confirmPassword): PasswordResponseInterface
    {
        $response = new PasswordResponse();

        try {
            // Get OTP from request data (this should be passed from the previous step)
            $otp = $this->request->getParam('otp');
            $email = $this->request->getParam('email');

            if (!$otp || !$email) {
                return $response->setStatus(false)
                    ->setMessage('OTP and email are required')
                    ->setData([])
                    ->setStatusCode(400);
            }

            // Find customer by email
            $customer = $this->customerRepository->get($email);
            if (!$customer || !$customer->getId()) {
                return $response->setStatus(false)
                    ->setMessage('Customer not found')
                    ->setData([])
                    ->setStatusCode(404);
            }

            $customerId = $customer->getId();

            // Check if there's a valid OTP for this customer
            $storedOtp = $this->otpFactory->create()
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('otp', $otp)
                ->addFieldToFilter('expires_at', ['gteq' => (new \DateTime())->format('Y-m-d H:i:s')])
                ->getFirstItem();

            if (!$storedOtp->getId()) {
                return $response->setStatus(false)
                    ->setMessage('OTP expired or not found')
                    ->setData([])
                    ->setStatusCode(401);
            }

            if ($newPassword !== $confirmPassword) {
                return $response->setStatus(false)
                    ->setMessage('New password and confirm password do not match')
                    ->setData([])
                    ->setStatusCode(400);
            }

            if (strlen($newPassword) < $this->getMinimumPasswordLength()) {
                return $response->setStatus(false)
                    ->setMessage('Password must be at least ' . $this->getMinimumPasswordLength() . ' characters long')
                    ->setData([])
                    ->setStatusCode(400);
            }

            // Update customer password
            $customerModel = $this->customerFactory->create()->load($customerId);
            $customerModel->setPassword($newPassword);
            $this->customerResourceModel->save($customerModel);

            // Delete the used OTP
            $this->otpResourceModel->delete($storedOtp);

            return $response->setStatus(true)
                ->setMessage('Password reset successfully')
                ->setData([])
                ->setStatusCode(200);

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $response->setStatus(false)
                ->setMessage('Customer not found')
                ->setData([])
                ->setStatusCode(404);
        } catch (\Exception $e) {
            $this->logger->critical('Error in resetPassword: ' . $e->getMessage());
            return $response->setStatus(false)
                ->setMessage($e->getMessage())
                ->setData([])
                ->setStatusCode(500);
        }
    }

    protected function generateOTP($length = 6)
    {
        return rand(pow(10, $length - 1), pow(10, $length) - 1);
    }

    private function sendOtpSms(string $phoneNumber, string $otp)
    {
        // Example SMS service integration (you can use Twilio, Nexmo, etc.)
        /*$smsService = new SmsService(); // Hypothetical SMS service class
        $smsService->sendSms($phoneNumber, 'Your OTP is: ' . $otp);*/

        // For now, just log the OTP for testing purposes
        $this->logger->info('SMS OTP for ' . $phoneNumber . ': ' . $otp);
        return true;
    }

    private function sendOtpEmail(string $email, string $otp)
    {
        try {
            // Prepare email variables
            $storeId = $this->storeManager->getStore()->getId();
            $templateId = 'password_reset_otp_template'; // Replace with your email template ID
            $sender = [
                'name' => $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE),
                'email' => $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE)
            ];

            // Prepare the variables to pass to the email template
            $templateVars = [
                'otp' => $otp,
                'store_name' => $this->storeManager->getStore()->getName(),
                'customer_email' => $email
            ];

            // Create email transport
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $storeId
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($email)
                ->getTransport();

            // Send the email
            $transport->sendMessage();

            return true;
        } catch (\Exception $e) {
            // Log the error or handle accordingly
            $this->logger->error('Error sending OTP email: ' . $e->getMessage());
            return false;
        }
    }
}
