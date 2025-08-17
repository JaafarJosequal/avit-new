<?php
namespace Josequal\APIMobile\Controller\Action;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\InputException;

abstract class Action extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface {
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var array
     */
    protected $_data;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encrypt;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customerModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($context);
        $this->request = $request;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $jsonHelper = $objectManager->create('\Magento\Framework\Json\Helper\Data');
        $this->jsonHelper = $jsonHelper;

        $encrypt = $objectManager->create('\Magento\Framework\Encryption\EncryptorInterface');
        $this->encrypt = $encrypt;

        $customerSession = $objectManager->create('\Magento\Customer\Model\Session');
        $this->customerSession = $customerSession;

        $this->customerModel = $objectManager->create('\Magento\Customer\Model\Customer');

        $this->storeManager = $objectManager->create('\Magento\Store\Model\StoreManagerInterface');

        $customer = $this->customerSession->getCustomer();
        if($customer && $customer->getStoreId() != $this->storeManager->getStore()->getId()){
            $customer->setStoreId($this->storeManager->getStore()->getId());
            $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
        }

        $this->setRequestData();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Convert data to JSON format
     *
     * @param mixed $data
     * @return string
     */
    public function dataToJson($data) {
        $this->setData($data);
        $this->dispatchEventChangeData($this->getActionName('_after'), ['controller' => $this, 'response_data' => &$data]);
        $this->_data = $this->getData();
        $json = $this->jsonHelper->jsonEncode($this->_data);
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $json;
            case JSON_ERROR_UTF8:
                $data = $this->utf8ize($this->_data);
                return $this->dataToJson($data);
            default:
                return $json;
        }
    }

    /**
     * Convert mixed data to UTF-8 encoding
     *
     * @param mixed $mixed
     * @return mixed
     */
    public function utf8ize($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
        }
        return $mixed;
    }

    /**
     * Dispatch event for data change
     *
     * @param string $event_name
     * @param array $data
     * @return void
     */
    public function dispatchEventChangeData($event_name, $data) {
        $this->_eventManager->dispatch($event_name, $data);
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request) {
        return parent::dispatch($request);
    }

    /**
     * Get action name
     *
     * @param string $last
     * @return string
     */
    public function getActionName($last = '') {
        return $this->getRequest()->getActionName() . $last;
    }

    /**
     * Print result as JSON
     *
     * @param mixed $data
     * @return void
     */
    public function printResult($data) {
        $json_data = $this->dataToJson($data);
        if (isset($_GET['callback']) && $_GET['callback'] != '') {
            print $_GET['callback'] . "(" . $json_data . ")";
        } else {
            header('content-type:application/json');
            echo $json_data;
        }
        exit;
    }

    /**
     * Print report result as JSON
     *
     * @param mixed $data
     * @return void
     */
    public function printReportResult($data) {
        $json_data = $this->dataToJson($data);
        header('content-type:application/json');
        echo $json_data;
        exit;
    }

    /**
     * Set request data
     *
     * @return void
     */
    public function setRequestData() {
        $this->_data = $this->getRequestData();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData() {
        return $this->_data;
    }

    /**
     * Get request data from parameters and body
     *
     * @return array
     */
    public function getRequestData() {
        $data = $this->getRequest()->getParams();

        // Try to get additional data from request body
        $input = file_get_contents('php://input');
        if ($input) {
            $jsonData = json_decode($input, true);
            if ($jsonData) {
                $data = array_merge($data, $jsonData);
            }
        }

        return $data;
    }

    /**
     * Authenticate customer
     *
     * @return int|void
     */
    public function auth(){
        try{
            if ($this->customerSession->isLoggedIn()) {
                return $this->customerSession->getCustomerId();
            }

            $token = $this->getAuthorizationHeader();
            if (!$token) {
                $this->printResult($this->errorStatus('Unauthorized',401));
                exit;
            }

            // Try TokenFactory way (ProfileService way)
            try {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $tokenFactory = $objectManager->create(\Magento\Integration\Model\Oauth\TokenFactory::class);
                $customerToken = $tokenFactory->create()->loadByToken($token);

                if ($customerToken && $customerToken->getCustomerId()) {
                    $customer = $this->customerModel->load($customerToken->getCustomerId());
                    $session = $this->customerSession->setCustomerAsLoggedIn($customer);
                    if ($session) {
                        return $customerToken->getCustomerId();
                    }
                }
            } catch (\Exception $e) {
                // TokenFactory failed, try encrypted token (APIOtherApp way)
                try {
                    $customer_id = $this->encrypt->decrypt($token);
                    if($customer_id){
                        $customer_id = (int) $customer_id;
                        $customer = $this->customerModel->load($customer_id);
                        $session = $this->customerSession->setCustomerAsLoggedIn($customer);
                        if ($session) {
                            return $customer_id;
                        }
                    }
                } catch (\Exception $e2) {
                    // Both methods failed
                }
            }

            $this->printResult($this->errorStatus('Unauthorized',401));
            exit;

        }catch(\Exception $e){
            $this->printResult($this->errorStatus('Unauthorized',401));
            exit;
        }
    }

    /**
     * Check if customer is authenticated
     *
     * @return bool
     */
    public function isAuth(){
        $token = $this->getAuthorizationHeader();
        if (!$token) {
            return false;
        }

        try {
            // Try TokenFactory way (ProfileService way)
            try {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $tokenFactory = $objectManager->create(\Magento\Integration\Model\Oauth\TokenFactory::class);
                $customerToken = $tokenFactory->create()->loadByToken($token);

                if ($customerToken && $customerToken->getCustomerId()) {
                    $this->customerSession->loginById($customerToken->getCustomerId());
                    return true;
                }
            } catch (\Exception $e) {
                // TokenFactory failed, try encrypted token (APIOtherApp way)
                try {
                    $customer_id = $this->encrypt->decrypt($token);
                    if($customer_id){
                        $this->customerSession->loginById($customer_id);
                        return true;
                    }
                } catch (\Exception $e2) {
                    // Both methods failed
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Get authorization header from request
     *
     * @return string|null
     */
    private function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Return error status response
     *
     * @param string|array $error
     * @param int $code
     * @return array
     */
    public function errorStatus($error = ['0', 'opps! unknown Error '],$code = 400) {
        http_response_code($code);
        return [
            'status' => false,
            'message' => is_array($error) ? $error[0] : $error,
            'data' => [],
        ];
    }

    /**
     * Set data
     *
     * @param array $data
     * @return void
     */
    public function setData($data) {
        $this->_data = $data;
    }

    /**
     * Execute action - abstract method
     *
     * @return void
     */
    public function execute() {
        // Abstract method - should be implemented by child classes
    }
}
