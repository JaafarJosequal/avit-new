<?php
namespace Josequal\APIMobile\Model;

class AbstractModel extends \Magento\Framework\Model\AbstractModel {

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $customerSession = $this->_objectManager->create('\Magento\Customer\Model\Session');
        $this->customerSession = $customerSession;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function successStatus($success = 'SUCCESS') {
        return [
            'status' => true,
            'message' => __($success),
        ];
    }

    public static function errorStatus($error = ['0', 'opps! unknown Error '],$code = 400) {
        http_response_code($code);
        return [
            'status' => false,
            'message' => is_array($error) ? __($error[0]) : __($error),
            'data' => [],
        ];
    }

    public function checkUserLoginSession() {
        return $this->customerSession->isLoggedIn();
    }

    public function getCustomerLoggedIngroup() {

        if($this->customerSession->isLoggedIn()) {

            $customerGroup =  $this->customerSession->getCustomer()->getGroupId();

        } else {

            $customerGroup = "0";
        }

        return $customerGroup;

    }

    public function _getStoreId() {
        try {
            if ($this->storeManager && $this->storeManager->getStore()) {
                return $this->storeManager->getStore()->getStoreId();
            }
            return 1; // Default store ID
        } catch (\Exception $e) {
            return 1; // Default store ID
        }
    }

    public function _getStoreName() {
        try {
            if ($this->storeManager && $this->storeManager->getStore()) {
                return $this->storeManager->getStore()->getName();
            }
            return 'Default Store'; // Default store name
        } catch (\Exception $e) {
            return 'Default Store'; // Default store name
        }
    }

    public function _getWebsiteId() {
        try {
            if ($this->storeManager && $this->storeManager->getWebsite()) {
                return $this->storeManager->getWebsite()->getWebsiteId();
            }
            return 1; // Default website ID
        } catch (\Exception $e) {
            return 1; // Default website ID
        }
    }

    public function getStoreConfig($path = '', $store = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $socpeConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $data = $socpeConfig->getValue($path, $scope, $store);
        return $data;
    }

    public function eventChangeData($name_event, $value) {
        $this->eventManager->dispatch($name_event, $value);
    }

    public function getCoreModel($modelPath) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection
        $productCollection */
        $model = $objectManager->create($modelPath);
        /** Apply filters here */
        return $model;
    }

    public function getCoreHelper($helperPath) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $helper = $objectManager->create($helperPath);
        /** Apply filters here */
        return $helper;
    }

    public function getMobiHelper($helperPath) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
        $helper = $objectManager->create($helperPath);

        /** Apply filters here */
        return $helper;
    }

    public function _getUrl($url, $params = []) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection
        $productCollection */
        $urlInterface = $objectManager->create('\Magento\Framework\UrlInterface');
        /** Apply filters here */
        return $urlInterface->getUrl($url, $params);
    }
}
