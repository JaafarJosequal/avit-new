<?php
namespace Josequal\APIMobile\Model\V1;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

class Cart extends \Josequal\APIMobile\Model\AbstractModel {

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $cartItemRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

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
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context, $registry, $storeManager, $eventManager);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $this->quoteRepository = $objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');
        $this->cartItemRepository = $objectManager->get('\Magento\Quote\Api\CartItemRepositoryInterface');
        $this->productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
        $this->priceHelper = $objectManager->get('\Magento\Framework\Pricing\Helper\Data');
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
    }

    /**
     * Add product to cart
     */
    public function addToCart($data) {
        try {
            if (!isset($data['product_id'])) {
                return $this->errorStatus(['Product ID is required']);
            }

            $productId = (int)$data['product_id'];
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
            $options = $this->prepareProductOptions($data);

            // Check if product exists
            try {
                $product = $this->productRepository->getById($productId);
            } catch (NoSuchEntityException $e) {
                return $this->errorStatus(['Product not found']);
            }

            // Check if product with same options already exists in cart
            $existingItem = $this->findExistingCartItem($productId, $options);

            if ($existingItem && !empty($options)) {
                // Update quantity for existing item
                $newQty = $existingItem->getQty() + $quantity;
                $existingItem->setQty($newQty);
                $this->cartItemRepository->save($existingItem);

                $message = "Quantity updated for existing item";
            } else {
                // Add new item to cart with options
                $buyRequest = new \Magento\Framework\DataObject();
                $buyRequest->setQty($quantity);

                // Add custom options
                if (!empty($options)) {
                    foreach ($options as $key => $value) {
                        $buyRequest->setData($key, $value);
                    }
                }

                $this->cart->addProduct($product, $buyRequest);
                $this->cart->save();
                $message = "Product added successfully";
            }

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_added', [
                'product' => $product,
                'quantity' => $quantity,
                'options' => $options
            ]);

            // Get updated cart info
            $cartInfo = $this->getCartInfo();

            return [
                'status' => true,
                'message' => $message,
                'data' => $cartInfo
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Get cart information
     */
    public function getCartInfo() {
        try {
            $quote = $this->cart->getQuote();
            $items = [];

            foreach ($quote->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                $options = $this->getItemOptions($item);

                // Format options for better display
                $formattedOptions = $this->formatOptionsForDisplay($options);

                $items[] = [
                    'id' => (string)$item->getItemId(),
                    'product_id' => (string)$item->getProductId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'qty' => (int)$item->getQty(),
                    'price' => $this->formatPrice($item->getPrice()),
                    'row_total' => $this->formatPrice($item->getRowTotal()),
                    'image' => $this->getProductImageUrl($product),
                    'options' => $formattedOptions,
                    'has_options' => !empty($formattedOptions),
                    'options_summary' => $this->getOptionsSummary($formattedOptions),
                    'is_available' => $this->isProductAvailable($product),
                    'stock_status' => $this->getStockStatus($product)
                ];
            }

            $totals = $this->getCartTotals($quote);

            return [
                'items' => $items,
                'cart_qty' => (int)$quote->getItemsQty(),
                'has_coupon' => $quote->getCouponCode() ? true : false,
                'coupon' => $quote->getCouponCode() ?: '',
                'totals' => $totals,
                'cart_id' => (string)$quote->getId(),
                'store_id' => (int)$this->storeManager->getStore()->getId()
            ];

        } catch (\Exception $e) {
            return [
                'items' => [],
                'cart_qty' => 0,
                'has_coupon' => false,
                'coupon' => '',
                'totals' => [],
                'cart_id' => '',
                'store_id' => 0
            ];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem($data) {
        try {
            if (!isset($data['item_id']) || !isset($data['qty'])) {
                return $this->errorStatus(['Item ID and quantity are required']);
            }

            $itemId = (int)$data['item_id'];
            $newQty = (int)$data['qty'];

            if ($newQty <= 0) {
                return $this->errorStatus(['Quantity must be greater than 0']);
            }

            $item = $this->cartItemRepository->get($itemId);
            $item->setQty($newQty);
            $this->cartItemRepository->save($item);

            $this->cart->save();

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_updated', [
                'item' => $item,
                'old_qty' => $item->getOrigData('qty'),
                'new_qty' => $newQty
            ]);

            $cartInfo = $this->getCartInfo();

            return [
                'status' => true,
                'message' => 'Cart updated successfully',
                'data' => $cartInfo
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Delete cart item
     */
    public function deleteCartItem($data) {
        try {
            if (isset($data['item_id'])) {
                // Delete specific item
                $itemId = (int)$data['item_id'];
                $item = $this->cartItemRepository->get($itemId);
                $this->cartItemRepository->deleteById($itemId);
                $message = "Item removed successfully";

                // Dispatch event for cart modification
                $this->eventManager->dispatch('josequal_cart_item_deleted', [
                    'item' => $item
                ]);

            } elseif (isset($data['product_id'])) {
                // Delete all items for specific product
                $productId = (int)$data['product_id'];
                $quote = $this->cart->getQuote();

                foreach ($quote->getAllVisibleItems() as $item) {
                    if ($item->getProductId() == $productId) {
                        $this->cartItemRepository->deleteById($item->getItemId());
                    }
                }
                $message = "All items for product removed successfully";
            } else {
                return $this->errorStatus(['Item ID or Product ID is required']);
            }

            $this->cart->save();
            $cartInfo = $this->getCartInfo();

            return [
                'status' => true,
                'message' => $message,
                'data' => $cartInfo
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart() {
        try {
            $quote = $this->cart->getQuote();
            $items = $quote->getAllVisibleItems();

            foreach ($items as $item) {
                $this->cartItemRepository->deleteById($item->getItemId());
            }

            $this->cart->save();

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_cleared', [
                'quote' => $quote
            ]);

            return [
                'status' => true,
                'message' => 'Cart cleared successfully',
                'data' => $this->getCartInfo()
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Apply coupon code
     */
    public function applyCoupon($data) {
        try {
            if (!isset($data['coupon_code'])) {
                return $this->errorStatus(['Coupon code is required']);
            }

            $couponCode = trim($data['coupon_code']);
            $quote = $this->cart->getQuote();

            $quote->setCouponCode($couponCode);
            $this->cart->save();

            // Dispatch event for coupon application
            $this->eventManager->dispatch('josequal_coupon_applied', [
                'coupon_code' => $couponCode,
                'quote' => $quote
            ]);

            $cartInfo = $this->getCartInfo();

            return [
                'status' => true,
                'message' => 'Coupon applied successfully',
                'data' => $cartInfo
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Remove coupon code
     */
    public function removeCoupon() {
        try {
            $quote = $this->cart->getQuote();
            $quote->setCouponCode('');
            $this->cart->save();

            // Dispatch event for coupon removal
            $this->eventManager->dispatch('josequal_coupon_removed', [
                'quote' => $quote
            ]);

            $cartInfo = $this->getCartInfo();

            return [
                'status' => true,
                'message' => 'Coupon removed successfully',
                'data' => $cartInfo
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Prepare product options from request data
     */
    private function prepareProductOptions($data) {
        $options = [];

        // Standard options
        if (isset($data['color']) && !empty($data['color'])) {
            $options['color'] = trim($data['color']);
        }

        if (isset($data['size']) && !empty($data['size'])) {
            $options['size'] = trim($data['size']);
        }

        // Additional custom options
        if (isset($data['custom_options']) && is_array($data['custom_options'])) {
            foreach ($data['custom_options'] as $key => $value) {
                if (!empty($value)) {
                    $options[$key] = trim($value);
                }
            }
        }

        // Check for any other option fields
        $optionFields = ['material', 'style', 'pattern', 'brand', 'model', 'weight', 'dimensions'];
        foreach ($optionFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $options[$field] = trim($data[$field]);
            }
        }

        return $options;
    }

    /**
     * Format options for better display
     */
    private function formatOptionsForDisplay($options) {
        if (empty($options)) {
            return [];
        }

        $formatted = [];

        // Map option keys to display names
        $optionLabels = [
            'color' => 'Color',
            'size' => 'Size',
            'material' => 'Material',
            'style' => 'Style',
            'pattern' => 'Pattern',
            'brand' => 'Brand',
            'model' => 'Model',
            'weight' => 'Weight',
            'dimensions' => 'Dimensions'
        ];

        foreach ($options as $key => $value) {
            if (!empty($value)) {
                $label = isset($optionLabels[$key]) ? $optionLabels[$key] : ucfirst($key);
                $formatted[] = [
                    'key' => $key,
                    'label' => $label,
                    'value' => $value
                ];
            }
        }

        return $formatted;
    }

    /**
     * Get options summary as text
     */
    private function getOptionsSummary($formattedOptions) {
        if (empty($formattedOptions)) {
            return '';
        }

        $summary = [];
        foreach ($formattedOptions as $option) {
            $summary[] = $option['label'] . ': ' . $option['value'];
        }

        return implode(', ', $summary);
    }

    /**
     * Find existing cart item with same product and options
     */
    private function findExistingCartItem($productId, $options) {
        $quote = $this->cart->getQuote();

        // If no options, don't merge quantities
        if (empty($options)) {
            return null;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getProductId() == $productId) {
                $itemOptions = $this->getItemOptions($item);

                // Compare options
                if ($this->compareOptions($options, $itemOptions)) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Compare two option arrays
     */
    private function compareOptions($options1, $options2) {
        // If both are empty, they are the same
        if (empty($options1) && empty($options2)) {
            return true;
        }

        // If one is empty and the other is not, they are different
        if (empty($options1) || empty($options2)) {
            return false;
        }

        // Check if all options in options1 exist in options2 with same values
        foreach ($options1 as $key => $value) {
            if (!isset($options2[$key]) || $options2[$key] !== $value) {
                return false;
            }
        }

        // Check if all options in options2 exist in options1 with same values
        foreach ($options2 as $key => $value) {
            if (!isset($options1[$key]) || $options1[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get item options
     */
    private function getItemOptions($item) {
        $options = [];

        try {
            // Get options from buy request
            if ($item->getBuyRequest()) {
                $buyRequest = $item->getBuyRequest();

                // Check for color option
                if ($buyRequest->getColor()) {
                    $options['color'] = $buyRequest->getColor();
                }

                // Check for size option
                if ($buyRequest->getSize()) {
                    $options['size'] = $buyRequest->getSize();
                }

                // Check for other custom options
                if ($buyRequest->getCustomOptions()) {
                    foreach ($buyRequest->getCustomOptions() as $key => $value) {
                        if (!isset($options[$key])) {
                            $options[$key] = $value;
                        }
                    }
                }
            }

            // Also check for product options if buy request doesn't have them
            if (empty($options)) {
                $productOptions = $item->getProductOptions();
                if (isset($productOptions['info_buyRequest'])) {
                    $infoBuyRequest = $productOptions['info_buyRequest'];
                    if (isset($infoBuyRequest['color'])) {
                        $options['color'] = $infoBuyRequest['color'];
                    }
                    if (isset($infoBuyRequest['size'])) {
                        $options['size'] = $infoBuyRequest['size'];
                    }
                }
            }

        } catch (\Exception $e) {
            // Log error if needed
        }

        return $options;
    }

    /**
     * Get product image URL
     */
    private function getProductImageUrl($product) {
        try {
            $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $imageUrl .= 'catalog/product' . $product->getImage();
            return $imageUrl;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format price
     */
    private function formatPrice($price) {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Check if product is available
     */
    private function isProductAvailable($product) {
        try {
            return $product->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get stock status
     */
    private function getStockStatus($product) {
        try {
            $stockItem = $product->getStockItem();
            if ($stockItem) {
                return [
                    'is_in_stock' => $stockItem->getIsInStock(),
                    'qty' => $stockItem->getQty(),
                    'min_qty' => $stockItem->getMinQty(),
                    'max_qty' => $stockItem->getMaxQty()
                ];
            }
            return [
                'is_in_stock' => false,
                'qty' => 0,
                'min_qty' => 0,
                'max_qty' => 0
            ];
        } catch (\Exception $e) {
            return [
                'is_in_stock' => false,
                'qty' => 0,
                'min_qty' => 0,
                'max_qty' => 0
            ];
        }
    }

    /**
     * Get cart totals
     */
    private function getCartTotals($quote) {
        $totals = [];

        try {
            // Subtotal
            $totals[] = [
                'label' => 'Subtotal',
                'value' => $this->formatPrice($quote->getSubtotal())
            ];

            // Shipping
            if ($quote->getShippingAddress() && $quote->getShippingAddress()->getShippingAmount()) {
                $totals[] = [
                    'label' => 'Shipping',
                    'value' => $this->formatPrice($quote->getShippingAddress()->getShippingAmount())
                ];
            } else {
                $totals[] = [
                    'label' => 'Shipping',
                    'value' => '$0.00'
                ];
            }

            // Tax
            if ($quote->getGrandTotal() > $quote->getSubtotal()) {
                $taxAmount = $quote->getGrandTotal() - $quote->getSubtotal();
                $totals[] = [
                    'label' => 'Tax',
                    'value' => $this->formatPrice($taxAmount)
                ];
            }

            // Discount
            if ($quote->getDiscountAmount() > 0) {
                $totals[] = [
                    'label' => 'Discount',
                    'value' => '-' . $this->formatPrice($quote->getDiscountAmount())
                ];
            }

            // Grand Total
            $totals[] = [
                'label' => 'Grand Total',
                'value' => $this->formatPrice($quote->getGrandTotal())
            ];

        } catch (\Exception $e) {
            $totals = [
                [
                    'label' => 'Subtotal',
                    'value' => '$0.00'
                ],
                [
                    'label' => 'Grand Total',
                    'value' => '$0.00'
                ]
            ];
        }

        return $totals;
    }
}
