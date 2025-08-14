<?php
namespace Josequal\APIMobile\Model\V1;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\UrlInterface;

class Cart extends \Josequal\APIMobile\Model\AbstractModel {

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    protected $checkoutSession;

    protected $productModel;

    protected $stockState;

    protected $currencyHelper;

    protected $scopeConfig;

    protected $imageBuilder;

    protected $objectManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context, $registry, $storeManager, $eventManager);

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
        $this->registry = $registry;

        $this->productModel = $this->objectManager->get('\Magento\Catalog\Model\Product');
        $this->cart = $this->objectManager->get('\Magento\Checkout\Model\Cart');
        $this->checkoutSession = $this->objectManager->get('\Magento\Checkout\Model\Session');
        $this->stockState = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $this->currencyHelper = $this->objectManager->get('\Magento\Framework\Pricing\Helper\Data');
        $this->imageBuilder = $this->objectManager->get('\Magento\Catalog\Block\Product\ImageBuilder');
        $this->scopeConfig = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->customerSession = $this->objectManager->get('\Magento\Customer\Model\Session');
    }

    //Add To Cart
    public function addToCart($data) {
        if(!isset($data['product_id'])){
            return $this->errorStatus(["Product is required"]);
        }

        $params['qty'] = isset($data['quantity']) ? (int) $data['quantity'] : 1;

        // Handle custom options (color, size, etc.)
        if (isset($data['options']) && is_array($data['options'])) {
            $params['options'] = $data['options'];
        }

        // Handle specific color and size options
        if (isset($data['color']) && !empty($data['color'])) {
            $params['options']['color'] = $data['color'];
        }

        if (isset($data['size']) && !empty($data['size'])) {
            $params['options']['size'] = $data['size'];
        }

        try {
            $product = $this->productModel->setStoreId($this->storeManager->getStore()->getId())->load($data['product_id']);
            if (!$product) {
                return $this->errorStatus(["Product not exist"],404);
            }

            // Check if product already exists in cart with same options
            $existingItem = $this->findExistingCartItem($product->getId(), $params['options'] ?? []);

            if ($existingItem) {
                // Update existing item quantity
                $newQty = $existingItem->getQty() + $params['qty'];
                $this->cart->updateItem($existingItem->getItemId(), ['qty' => $newQty]);
                $message = 'Product quantity updated successfully';
            } else {
                // Add new item
                $this->cart->addProduct($product, $params);
                $message = 'Product added successfully';
            }

            $this->cart->save();

            $info = $this->successStatus($message);
            $info['data'] = $this->getCartDetails();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->errorStatus($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorStatus($e->getMessage());
        }

        return $info;
    }

    /**
     * Find existing cart item with same product and options
     */
    private function findExistingCartItem($productId, $options = []) {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            if ($item->getProduct()->getId() == $productId) {
                // Check if options match
                $itemOptions = $this->getItemOptions($item);
                if ($this->compareOptions($itemOptions, $options)) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Get formatted options from cart item
     */
    private function getItemOptions($item) {
        $options = [];
        $itemOptions = $item->getOptions();

        if ($itemOptions) {
            foreach ($itemOptions as $option) {
                // Handle different option formats
                if (is_array($option)) {
                    if (isset($option['code']) && isset($option['value'])) {
                        $options[$option['code']] = $option['value'];
                    } elseif (isset($option['label']) && isset($option['value'])) {
                        // Convert label to code format
                        $code = strtolower(str_replace(' ', '_', $option['label']));
                        $options[$code] = $option['value'];
                    }
                } elseif (is_object($option)) {
                    if (method_exists($option, 'getCode') && method_exists($option, 'getValue')) {
                        $options[$option->getCode()] = $option->getValue();
                    } elseif (method_exists($option, 'getLabel') && method_exists($option, 'getValue')) {
                        $code = strtolower(str_replace(' ', '_', $option->getLabel()));
                        $options[$code] = $option->getValue();
                    }
                }
            }
        }

        return $options;
    }

    /**
     * Compare two option arrays
     */
    private function compareOptions($options1, $options2) {
        if (empty($options1) && empty($options2)) {
            return true;
        }

        if (empty($options1) || empty($options2)) {
            return false;
        }

        foreach ($options1 as $key => $value) {
            if (!isset($options2[$key]) || $options2[$key] != $value) {
                return false;
            }
        }

        foreach ($options2 as $key => $value) {
            if (!isset($options1[$key]) || $options1[$key] != $value) {
                return false;
            }
        }

        return true;
    }

    public function getCartInfo($data = []) {
        $info = $this->successStatus('Cart Details');
        $info['data'] = $this->getCartDetails();
        return $info;
    }

    //Get cart Details
    public function getCartDetails() {
        $quote = $this->checkoutSession->getQuote();
        $quote->collectTotals();
        $quote->save();

        $list = [];
        $items = $quote->getAllVisibleItems();

        $discountAmount = 0;
        foreach ($items as $item) {
            $productData = $this->processProduct($item);
            $list[] = $productData;

            $baseDiscountAmount = $item->getBasePrice() - $item->getBaseRowTotal();
            $discountAmount += $baseDiscountAmount * $item->getQty();
        }

        $coupon = $quote->getCouponCode();

        $data['items'] = $list;
        $data['cart'] = !empty($list);
        $data['has_coupon'] = $coupon != null;
        $data['coupon'] = $coupon ? $coupon : '';
        $data['cart_qty'] = $quote->getItemsSummaryQty();
        $data['minimum_order'] = $this->scopeConfig->getValue('sales/minimum_order/amount', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $data['minimum_description'] = $this->scopeConfig->getValue('sales/minimum_order/description', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $totals = $quote->getTotals();

        $data['totals'][] = [
            'label' => __('Subtotal'),
            'value' => $this->currencyHelper->currency($quote->getSubtotal(), true, false)
        ];

        if (isset($totals['shipping'])) {
            $data['totals'][] = [
                'label' => __('Shipping'),
                'value' => $this->currencyHelper->currency($totals['shipping']->getValue(), true, false)
            ];
        }

        if (isset($totals['discount'])) {
            $data['totals'][] = [
                'label' => __('Discount'),
                'value' => $this->currencyHelper->currency($totals['discount']->getValue(), true, false)
            ];
        }

        $data['totals'][] = [
            'label' => __('Grand Total'),
            'value' => $this->currencyHelper->currency($quote->getGrandTotal(), true, false)
        ];

        return $data;
    }

    public function processProduct($item) {
        $product = $item->getProduct();

        $productData = [
            'id' => $item->getItemId(),
            'product_id' => $product->getId(),
            'name' => $item->getName(),
            'sku' => $item->getSku(),
            'qty' => $item->getQty(),
            'price' => $this->currencyHelper->currency($item->getPrice(), true, false),
            'row_total' => $this->currencyHelper->currency($item->getRowTotal(), true, false),
            'image' => $this->getImage($product, 'product_thumbnail_image'),
            'options' => $this->formatCartOptions($item->getOptions())
        ];

        return $productData;
    }

    public function getImage($product, $imageId, $attributes = []) {
        return $this->imageBuilder->setProduct($product)->setImageId($imageId)->setAttributes($attributes)->create();
    }

    private function formatCartOptions($options) {
        $formattedOptions = [];
        if ($options) {
            foreach ($options as $option) {
                $formattedOptions[] = [
                    'label' => $option['label'],
                    'value' => $option['value']
                ];
            }
        }
        return $formattedOptions;
    }

    public function updateCart($data) {
        if(!isset($data['item_id'])){
            return $this->errorStatus(["Item ID is required"]);
        }

        $qty = isset($data['qty']) ? (int) $data['qty'] : 1;

        try {
            $this->cart->updateItem($data['item_id'], ['qty' => $qty]);
            $this->cart->save();

            $info = $this->successStatus('Cart updated successfully');
            $info['data'] = $this->getCartDetails();

        } catch (\Exception $e) {
            return $this->errorStatus($e->getMessage());
        }

        return $info;
    }

    public function deleteItem($data) {
        if(!isset($data['item_id'])){
            return $this->errorStatus(["Item ID is required"]);
        }

        try {
            $this->cart->removeItem($data['item_id']);
            $this->cart->save();

            $info = $this->successStatus('Item removed successfully');
            $info['data'] = $this->getCartDetails();

        } catch (\Exception $e) {
            return $this->errorStatus($e->getMessage());
        }

        return $info;
    }
}
