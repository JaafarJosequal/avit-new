<?php
namespace Josequal\APIMobile\Model\V1;

class Order extends \Josequal\APIMobile\Model\AbstractModel
{

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    protected $customerSession;
    protected $_checkoutSession;
    protected $currencyHelper;
    protected $OnePageModel;
    protected $imageBuilder;
    protected $quoteManagement;
    protected $_countryFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Customer\Model\Session $customerSession
    ) {

        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;

        parent::__construct($context, $registry, $storeManager, $eventManager);

        $this->customerSession = $customerSession;

        //new
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->orderModel = $this->objectManager->get('\Magento\Sales\Model\Order');
        $this->timezoneInterface = $this->objectManager->get('\Magento\Framework\Stdlib\DateTime\TimezoneInterface');
        $this->productHelper = $this->objectManager->get('\Magento\Catalog\Helper\Product');
        $this->_assetRepo = $this->objectManager->get('\Magento\Framework\View\Asset\Repository');
        $this->currencyHelper = $this->objectManager->get('\Magento\Framework\Pricing\Helper\Data');
        $this->_countryFactory = $this->objectManager->get('\Magento\Directory\Model\CountryFactory');

        date_default_timezone_set("Asia/Riyadh");

    }

    function getCustomerOrders($data){

        $page = isset($data['page']) ? $data['page'] : 1;

        $limit = 20;

        $collection = $this->orderModel->getCollection()->addFieldToFilter('customer_id', $this->customerSession->getCustomer()->getId())
            ->setOrder('entity_id', 'DESC');

        $ordersCount = $collection->getSize();

        $collection->getSelect()->limit($limit, ($page - 1) * $limit);
        $orders = [];
        if (count($collection) > 0) {
            foreach ($collection as $_collection) {

                $orders[] = $this->formatOrder($_collection);
            }
        }

        $data['ordersCount'] = $ordersCount;
        $data['orders'] = $orders;

        $info = $this->successStatus('Customer Orders');
        $info['data'] = $data;
        return $info;
    }

    function formatOrder($_collection){
        $items = $this->getProductFromOrderDetail($_collection);
        if(!empty($items)){
            $image = $items[0]['product_image'];
        }else{
            $image = $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
        }
        $billing = $_collection->getBillingAddress();

        return [
            'id' => $_collection->getId(),
            'increment_id' => $_collection->getIncrementId(),
            'status' => $_collection->getStatus(),
            'status_label' => $_collection->getStatusLabel(),
            'created_at' => $this->timezoneInterface->formatDate($_collection->getCreatedAt()),
            'updated_at' => $this->timezoneInterface->formatDate($_collection->getUpdatedAt()),
            'total' => $this->currencyHelper->currency($_collection->getGrandTotal(), true, false),
            'subtotal' => $this->currencyHelper->currency($_collection->getSubtotal(), true, false),
            'shipping_amount' => $this->currencyHelper->currency($_collection->getShippingAmount(), true, false),
            'discount_amount' => $this->currencyHelper->currency($_collection->getDiscountAmount(), true, false),
            'tax_amount' => $this->currencyHelper->currency($_collection->getTaxAmount(), true, false),
            'items' => $items,
            'image' => $image,
            'billing_address' => $billing ? [
                'firstname' => $billing->getFirstname(),
                'lastname' => $billing->getLastname(),
                'street' => $billing->getStreet(),
                'city' => $billing->getCity(),
                'region' => $billing->getRegion(),
                'postcode' => $billing->getPostcode(),
                'country_id' => $billing->getCountryId(),
                'telephone' => $billing->getTelephone()
            ] : [],
            'shipping_address' => $_collection->getShippingAddress() ? [
                'firstname' => $_collection->getShippingAddress()->getFirstname(),
                'lastname' => $_collection->getShippingAddress()->getLastname(),
                'street' => $_collection->getShippingAddress()->getStreet(),
                'city' => $_collection->getShippingAddress()->getCity(),
                'region' => $_collection->getShippingAddress()->getRegion(),
                'postcode' => $_collection->getShippingAddress()->getPostcode(),
                'country_id' => $_collection->getShippingAddress()->getCountryId(),
                'telephone' => $_collection->getShippingAddress()->getTelephone()
            ] : []
        ];
    }

    public function getProductFromOrderDetail($order) {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $items[] = [
                'id' => $item->getItemId(),
                'product_id' => $product->getId(),
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'qty' => $item->getQty(),
                'price' => $this->currencyHelper->currency($item->getPrice(), true, false),
                'row_total' => $this->currencyHelper->currency($item->getRowTotal(), true, false),
                'product_image' => $this->getImage($product, 'product_thumbnail_image'),
                'options' => $this->getOptions('order', $item->getProductOptions())
            ];
        }
        return $items;
    }

    public function getOptions($type, $options) {
        $data = [];
        if ($options && isset($options['options'])) {
            foreach ($options['options'] as $option) {
                $data[] = [
                    'option_id' => isset($option['option_id']) ? $option['option_id'] : 0,
                    'option_value_id' => isset($option['option_value']) ? $option['option_value'] : 0,
                    'option_title' => $option['label'],
                    'option_value' => $option['value'],
                    'option_price' => isset($option['price']) ? $option['price'] : 0,
                ];
            }
        }
        return $data;
    }

    public function getOrderDetail($data) {
        if(!isset($data['order_id'])){
            return $this->errorStatus(["Order ID is required"]);
        }

        $order = $this->orderModel->load($data['order_id']);
        if (!$order->getId()) {
            return $this->errorStatus(["Order not found"], 404);
        }

        if ($order->getCustomerId() != $this->customerSession->getCustomer()->getId()) {
            return $this->errorStatus(["Unauthorized"], 403);
        }

        $info = $this->successStatus('Order Details');
        $info['data'] = $this->formatOrder($order);
        return $info;
    }

    public function getImage($product, $imageId, $attributes = []) {
        if ($product) {
            return $this->productHelper->getImageUrl($product);
        }
        return $this->_assetRepo->getUrl('Magento_Catalog::images/product/placeholder/image.jpg');
    }
}
