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
    protected $jsonHelper;
    protected $_data;
    protected $encrypt;
    protected $customerSession;
    protected $customerModel;
    protected $storeManager;

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

    public function dispatchEventChangeData($event_name, $data) {
        $this->_eventManager->dispatch($event_name, $data);
    }

    public function dispatch(RequestInterface $request) {
        return parent::dispatch($request);
    }

    public function getActionName($last = '') {
        return $this->getRequest()->getActionName() . $last;
    }

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

    public function printReportResult($data) {
        $json_data = $this->dataToJson($data);
        header('content-type:application/json');
        echo $json_data;
        exit;
    }

    public function setRequestData() {
        $this->_data = $this->getRequestData();
    }

    public function getData() {
        return $this->_data;
    }

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

    //Customer Auth
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

    //get Auth from header
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

    public function errorStatus($error = ['0', 'opps! unknown Error '],$code = 400) {
        http_response_code($code);
        return [
            'status' => false,
            'message' => is_array($error) ? $error[0] : $error,
            'data' => [],
        ];
    }

    public function setData($data) {
        $this->_data = $data;
    }

    public function execute() {
        // Abstract method - should be implemented by child classes
    }
}
