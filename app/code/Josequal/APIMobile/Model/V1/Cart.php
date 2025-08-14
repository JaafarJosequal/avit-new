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
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

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

            // Always add as new item to ensure options are preserved
            $this->cart->addProduct($product, $params);
            $this->cart->save();

            $info = $this->successStatus('Product added successfully');
            $info['data'] = $this->getCartDetails();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->errorStatus($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorStatus($e->getMessage());
        }

        return $info;
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

        // Display each item separately without grouping
        foreach ($items as $item) {
            $productData = $this->processProduct($item);
            $list[] = $productData;
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
                try {
                    $label = null;
                    $value = null;
                    $optionData = [];

                    // Handle different option formats
                    if (is_array($option)) {
                        $label = isset($option['label']) ? $option['label'] : null;
                        $value = isset($option['value']) ? $option['value'] : null;
                    } elseif (is_object($option)) {
                        $label = method_exists($option, 'getLabel') ? $option->getLabel() : null;
                        $value = method_exists($option, 'getValue') ? $option->getValue() : null;
                    }

                    // If value is JSON string, decode it
                    if (is_string($value) && !empty($value)) {
                        $decodedValue = json_decode($value, true);
                        if (is_array($decodedValue)) {
                            $optionData = $decodedValue;
                        } else {
                            $optionData = ['raw_value' => $value];
                        }
                    } else {
                        $optionData = ['raw_value' => $value];
                    }

                    // Extract specific options like color and size
                    $extractedOptions = [];
                    if (isset($optionData['options']) && is_array($optionData['options'])) {
                        foreach ($optionData['options'] as $key => $val) {
                            $extractedOptions[] = [
                                'type' => $key,
                                'value' => $val
                            ];
                        }
                    }

                    // Create formatted option
                    $formattedOption = [
                        'label' => $label,
                        'value' => $optionData,
                        'extracted_options' => $extractedOptions
                    ];

                    // Add specific color and size if available
                    if (isset($optionData['options']['color'])) {
                        $formattedOption['color'] = $optionData['options']['color'];
                    }
                    if (isset($optionData['options']['size'])) {
                        $formattedOption['size'] = $optionData['options']['size'];
                    }

                    $formattedOptions[] = $formattedOption;

                } catch (\Exception $e) {
                    // If there's an error processing this option, add it as is
                    $formattedOptions[] = [
                        'label' => is_array($option) ? ($option['label'] ?? null) : null,
                        'value' => is_array($option) ? ($option['value'] ?? null) : null,
                        'error' => 'Failed to process option'
                    ];
                }
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
        if(!isset($data['item_id']) && !isset($data['product_id'])){
            return $this->errorStatus(["Item ID or Product ID is required"]);
        }

        try {
            if (isset($data['item_id'])) {
                // Delete by item ID (direct deletion)
                $this->cart->removeItem($data['item_id']);
                $message = 'Item removed successfully';
            } else {
                // Delete by product ID (all items of this product)
                $productId = $data['product_id'];

                // Find all items with this product ID
                $quote = $this->checkoutSession->getQuote();
                $items = $quote->getAllVisibleItems();
                $removedCount = 0;

                foreach ($items as $item) {
                    if ($item->getProduct()->getId() == $productId) {
                        $this->cart->removeItem($item->getItemId());
                        $removedCount++;
                    }
                }

                if ($removedCount > 0) {
                    $message = "Removed {$removedCount} item(s) of product ID {$productId}";
                } else {
                    return $this->errorStatus(["Product not found in cart"]);
                }
            }

            $this->cart->save();

            $info = $this->successStatus($message);
            $info['data'] = $this->getCartDetails();

        } catch (\Exception $e) {
            return $this->errorStatus($e->getMessage());
        }

        return $info;
    }
}
