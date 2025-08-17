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

    /**
     * Debug log file path
     */
    protected $debugLogFile = 'var/log/cart_debug.log';

    /**
     * Log debug information
     *
     * @param string $message
     * @return void
     */
    protected function logDebug($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [CART DEBUG] $message" . PHP_EOL;

        // Write to debug log file
        $logDir = dirname($this->debugLogFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($this->debugLogFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Also output to console for immediate debugging
        echo "<!-- DEBUG: $message -->\n";
    }

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
            // Debug: Log input data
            $this->logDebug('=== ADD TO CART START ===');
            $this->logDebug('Input data: ' . json_encode($data));

            if (!isset($data['product_id'])) {
                $this->logDebug('ERROR: Product ID is required');
                return $this->errorStatus(['Product ID is required']);
            }

            $productId = (int)$data['product_id'];
            $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
            $options = $this->prepareProductOptions($data);

            $this->logDebug("Product ID: $productId");
            $this->logDebug("Quantity: $quantity");
            $this->logDebug("Prepared options: " . json_encode($options));

            // Check if product exists
            try {
                $product = $this->productRepository->getById($productId);
                $this->logDebug("Product found: " . $product->getName());
            } catch (NoSuchEntityException $e) {
                $this->logDebug('ERROR: Product not found - ' . $e->getMessage());
                return $this->errorStatus(['Product not found']);
            }

            // Check if product with exactly same options already exists in cart
            $this->logDebug('Searching for existing cart item...');
            $existingItem = $this->findExistingCartItem($productId, $options);

            if ($existingItem) {
                $this->logDebug('Existing item found with ID: ' . $existingItem->getItemId());
                $existingItemOptions = $this->getItemOptions($existingItem);
                $this->logDebug('Existing item options: ' . json_encode($existingItemOptions));

                $this->logDebug('Comparing options...');
                $optionsMatch = $this->compareOptions($options, $existingItemOptions);
                $this->logDebug('Options match: ' . ($optionsMatch ? 'YES' : 'NO'));

                if ($optionsMatch && !empty($options)) {
                    // Update quantity for existing item with identical options
                    $oldQty = $existingItem->getQty();
                    $newQty = $oldQty + $quantity;
                    $existingItem->setQty($newQty);
                    $this->cartItemRepository->save($existingItem);

                    $this->logDebug("Quantity updated: $oldQty -> $newQty");
                    $message = "Quantity updated for existing item with identical options";
                } else {
                    // Options are different, add as new item
                    $this->logDebug('Options are different, adding as new item');

                    $buyRequest = new \Magento\Framework\DataObject();
                    $buyRequest->setQty($quantity);

                    // Add custom options
                    if (!empty($options)) {
                        foreach ($options as $key => $value) {
                            $buyRequest->setData($key, $value);
                            $this->logDebug("Setting option: $key = $value");
                        }
                    }

                    $this->cart->addProduct($product, $buyRequest);
                    $this->cart->save();
                    $message = "Product added successfully with different options";
                    $this->logDebug('New item added to cart');
                }
            } else {
                $this->logDebug('No existing item found, adding new item');

                // Add new item to cart with options
                $buyRequest = new \Magento\Framework\DataObject();
                $buyRequest->setQty($quantity);

                // Add custom options
                if (!empty($options)) {
                    foreach ($options as $key => $value) {
                        $buyRequest->setData($key, $value);
                        $this->logDebug("Setting option: $key = $value");
                    }
                }

                $this->cart->addProduct($product, $buyRequest);
                $this->cart->save();
                $message = "Product added successfully";
                $this->logDebug('New item added to cart');
            }

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_added', [
                'product' => $product,
                'quantity' => $quantity,
                'options' => $options
            ]);

            // Get updated cart info
            $this->logDebug('Getting updated cart info...');
            $cartInfo = $this->getCartInfo();

            $this->logDebug("Final message: $message");
            $this->logDebug('=== ADD TO CART END ===');

            return [
                'status' => true,
                'message' => $message,
                'data' => $cartInfo
            ];

        } catch (\Exception $e) {
            $this->logDebug('EXCEPTION in addToCart: ' . $e->getMessage());
            $this->logDebug('Stack trace: ' . $e->getTraceAsString());
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Get cart information
     */
    public function getCartInfo() {
        try {
            $this->logDebug("=== GET CART INFO START ===");

            $quote = $this->cart->getQuote();
            $this->logDebug("Cart quote ID: " . $quote->getId());

            // Debug all items (including hidden ones)
            $allItems = $quote->getAllItems();
            $this->logDebug("Total all items (including hidden): " . count($allItems));

            $visibleItems = $quote->getAllVisibleItems();
            $this->logDebug("Total visible items: " . count($visibleItems));

            // Debug each item
            foreach ($allItems as $item) {
                $this->logDebug("Item ID: " . $item->getItemId() .
                               ", Product ID: " . $item->getProductId() .
                               ", Qty: " . $item->getQty() .
                               ", Visible: " . ($item->getIsVisible() ? 'YES' : 'NO') .
                               ", Parent: " . ($item->getParentItemId() ?: 'NONE'));
            }

            $items = [];

            foreach ($visibleItems as $item) {
                $this->logDebug("Processing visible item ID: " . $item->getItemId());
                $this->logDebug("Item product ID: " . $item->getProductId());
                $this->logDebug("Item quantity: " . $item->getQty());

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

                $this->logDebug("Item added to response with options: " . json_encode($formattedOptions));
            }

            $this->logDebug("Final items count: " . count($items));

            $totals = $this->getCartTotals($quote);

            $result = [
                'items' => $items,
                'cart_qty' => (int)$quote->getItemsQty(),
                'has_coupon' => $quote->getCouponCode() ? true : false,
                'coupon' => $quote->getCouponCode() ?: '',
                'totals' => $totals,
                'cart_id' => (string)$quote->getId(),
                'store_id' => (int)$this->storeManager->getStore()->getId()
            ];

            $this->logDebug("Cart info result: " . json_encode($result));
            $this->logDebug("=== GET CART INFO END ===");

            return $result;

        } catch (\Exception $e) {
            $this->logDebug('EXCEPTION in getCartInfo: ' . $e->getMessage());
            $this->logDebug('Stack trace: ' . $e->getTraceAsString());

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
        $this->logDebug("=== PREPARE PRODUCT OPTIONS ===");
        $this->logDebug("Input data: " . json_encode($data));

        $options = [];

        // Standard options
        if (isset($data['color']) && !empty($data['color'])) {
            $options['color'] = trim($data['color']);
            $this->logDebug("Added color option: " . $options['color']);
        }

        if (isset($data['size']) && !empty($data['size'])) {
            $options['size'] = trim($data['size']);
            $this->logDebug("Added size option: " . $options['size']);
        }

        // Additional custom options
        if (isset($data['custom_options']) && is_array($data['custom_options'])) {
            $this->logDebug("Custom options found: " . json_encode($data['custom_options']));
            foreach ($data['custom_options'] as $key => $value) {
                if (!empty($value)) {
                    $options[$key] = trim($value);
                    $this->logDebug("Added custom option: $key = $value");
                }
            }
        }

        // Check for any other option fields
        $optionFields = ['material', 'style', 'pattern', 'brand', 'model', 'weight', 'dimensions'];
        foreach ($optionFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $options[$field] = trim($data[$field]);
                $this->logDebug("Added field option: $field = $options[$field]");
            }
        }

        $this->logDebug("Final prepared options: " . json_encode($options));
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
        $this->logDebug("=== FIND EXISTING CART ITEM ===");
        $this->logDebug("Product ID: $productId");
        $this->logDebug("Options to find: " . json_encode($options));

        $quote = $this->cart->getQuote();
        $this->logDebug("Cart quote ID: " . $quote->getId());

        // If no options, don't merge quantities
        if (empty($options)) {
            $this->logDebug("No options provided, returning null");
            return null;
        }

        $allItems = $quote->getAllVisibleItems();
        $this->logDebug("Total cart items: " . count($allItems));

        foreach ($allItems as $item) {
            $this->logDebug("Checking item ID: " . $item->getItemId());
            $this->logDebug("Item product ID: " . $item->getProductId());

            if ($item->getProductId() == $productId) {
                $this->logDebug("Product ID matches, checking options...");
                $itemOptions = $this->getItemOptions($item);
                $this->logDebug("Item options: " . json_encode($itemOptions));

                // Compare options - must be exactly the same
                $optionsMatch = $this->compareOptions($options, $itemOptions);
                $this->logDebug("Options match: " . ($optionsMatch ? 'YES' : 'NO'));

                if ($optionsMatch) {
                    $this->logDebug("Found matching item with ID: " . $item->getItemId());
                    return $item;
                }
            }
        }

        $this->logDebug("No matching item found");
        return null;
    }

    /**
     * Compare two option arrays - must be exactly identical
     */
    private function compareOptions($options1, $options2) {
        $this->logDebug("=== COMPARE OPTIONS ===");
        $this->logDebug("Options1: " . json_encode($options1));
        $this->logDebug("Options2: " . json_encode($options2));

        // If both are empty, they are the same
        if (empty($options1) && empty($options2)) {
            $this->logDebug("Both options are empty - MATCH");
            return true;
        }

        // If one is empty and the other is not, they are different
        if (empty($options1) || empty($options2)) {
            $this->logDebug("One set is empty - NO MATCH");
            return false;
        }

        // Check if arrays have the same number of keys
        if (count($options1) !== count($options2)) {
            $this->logDebug("Different number of keys: " . count($options1) . " vs " . count($options2) . " - NO MATCH");
            return false;
        }

        // Check if all keys exist and have exactly the same values
        foreach ($options1 as $key => $value) {
            if (!isset($options2[$key])) {
                $this->logDebug("Key '$key' missing in options2 - NO MATCH");
                return false;
            } elseif ($options2[$key] !== $value) {
                $this->logDebug("Value mismatch for '$key': '$value' vs '{$options2[$key]}' - NO MATCH");
                return false;
            }
        }

        // Double check - ensure all keys in options2 exist in options1
        foreach ($options2 as $key => $value) {
            if (!isset($options1[$key]) || $options1[$key] !== $value) {
                $this->logDebug("Key '$key' missing or different in options1 - NO MATCH");
                return false;
            }
        }

        $this->logDebug("All options match - MATCH");
        return true;
    }

    /**
     * Get item options
     */
    private function getItemOptions($item) {
        $this->logDebug("=== GET ITEM OPTIONS ===");
        $this->logDebug("Item ID: " . $item->getItemId());

        $options = [];

        try {
            // Get options from buy request
            if ($item->getBuyRequest()) {
                $this->logDebug("Buy request exists, extracting options...");
                $buyRequest = $item->getBuyRequest();

                // Check for color option
                if ($buyRequest->getColor()) {
                    $options['color'] = $buyRequest->getColor();
                    $this->logDebug("Found color option: " . $options['color']);
                }

                // Check for size option
                if ($buyRequest->getSize()) {
                    $options['size'] = $buyRequest->getSize();
                    $this->logDebug("Found size option: " . $options['size']);
                }

                // Check for other custom options
                if ($buyRequest->getCustomOptions()) {
                    $this->logDebug("Custom options found: " . json_encode($buyRequest->getCustomOptions()));
                    foreach ($buyRequest->getCustomOptions() as $key => $value) {
                        if (!isset($options[$key])) {
                            $options[$key] = $value;
                            $this->logDebug("Added custom option: $key = $value");
                        }
                    }
                }

                // Check for any other option fields that might be set
                $optionFields = ['material', 'style', 'pattern', 'brand', 'model', 'weight', 'dimensions'];
                foreach ($optionFields as $field) {
                    $value = $buyRequest->getData($field);
                    if ($value && !empty($value)) {
                        $options[$field] = $value;
                        $this->logDebug("Added field option: $field = $value");
                    }
                }
            } else {
                $this->logDebug("No buy request found");
            }

            // Also check for product options if buy request doesn't have them
            if (empty($options)) {
                $this->logDebug("No options from buy request, checking product options...");
                $productOptions = $item->getProductOptions();
                if (isset($productOptions['info_buyRequest'])) {
                    $infoBuyRequest = $productOptions['info_buyRequest'];
                    $this->logDebug("Product options buy request: " . json_encode($infoBuyRequest));

                    if (isset($infoBuyRequest['color'])) {
                        $options['color'] = $infoBuyRequest['color'];
                        $this->logDebug("Found color from product options: " . $options['color']);
                    }
                    if (isset($infoBuyRequest['size'])) {
                        $options['size'] = $infoBuyRequest['size'];
                        $this->logDebug("Found size from product options: " . $options['size']);
                    }

                    // Check for other options
                    foreach ($optionFields as $field) {
                        if (isset($infoBuyRequest[$field]) && !empty($infoBuyRequest[$field])) {
                            $options[$field] = $infoBuyRequest[$field];
                            $this->logDebug("Found $field from product options: " . $options[$field]);
                        }
                    }
                } else {
                    $this->logDebug("No product options found");
                }
            }

        } catch (\Exception $e) {
            $this->logDebug("Exception in getItemOptions: " . $e->getMessage());
        }

        $this->logDebug("Final extracted options: " . json_encode($options));
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

    /**
     * Test options comparison for debugging
     *
     * @param array $options1
     * @param array $options2
     * @return array
     */
    public function testOptionsComparison($options1, $options2) {
        $result = [
            'options1' => $options1,
            'options2' => $options2,
            'are_identical' => $this->compareOptions($options1, $options2),
            'comparison_details' => []
        ];

        // Detailed comparison
        if (empty($options1) && empty($options2)) {
            $result['comparison_details'][] = 'Both options are empty';
        } elseif (empty($options1) || empty($options2)) {
            $result['comparison_details'][] = 'One set of options is empty';
        } else {
            if (count($options1) !== count($options2)) {
                $result['comparison_details'][] = 'Different number of options: ' . count($options1) . ' vs ' . count($options2);
            }

            foreach ($options1 as $key => $value) {
                if (!isset($options2[$key])) {
                    $result['comparison_details'][] = "Key '$key' missing in options2";
                } elseif ($options2[$key] !== $value) {
                    $result['comparison_details'][] = "Value mismatch for '$key': '$value' vs '{$options2[$key]}'";
                }
            }

            foreach ($options2 as $key => $value) {
                if (!isset($options1[$key])) {
                    $result['comparison_details'][] = "Key '$key' missing in options1";
                }
            }
        }

        return $result;
    }
}
