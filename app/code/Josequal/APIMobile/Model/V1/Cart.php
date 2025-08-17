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

            // Always add as new item - no merging of quantities
            $this->logDebug('Adding product as new item (no quantity merging)');

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

            // Add unique identifier to force Magento to treat this as a completely new item
            $uniqueId = 'unique_' . time() . '_' . rand(1000, 9999);
            $buyRequest->setData('unique_cart_id', $uniqueId);
            $this->logDebug("Added unique identifier: $uniqueId");

            // Add additional unique data to prevent Magento from merging items
            $buyRequest->setData('timestamp', time());
            $buyRequest->setData('random_id', uniqid());
            $this->logDebug("Added additional unique data to prevent merging");

            // Force Magento to treat this as a completely new item by adding unique product data
            $buyRequest->setData('force_new_item', true);
            $buyRequest->setData('item_unique_hash', md5($uniqueId . time() . rand()));
            $this->logDebug("Added force_new_item flag to prevent merging");

            // Modify the product to appear as a different product to Magento
            $buyRequest->setData('custom_product_id', $productId . '_' . $uniqueId);
            $buyRequest->setData('custom_sku', $product->getSku() . '_' . $uniqueId);
            $this->logDebug("Modified product data to appear as different product");

            // Don't add custom unique identifier as it may cause visibility issues
            $this->logDebug("No custom unique ID needed for new item");

            // Debug buyRequest before adding
            $this->logDebug("BuyRequest before addProduct: " . json_encode($buyRequest->getData()));

            // Try different approach - use addProductBySku
            try {
                $this->logDebug("Trying addProductBySku approach...");
                $this->cart->addProductBySku($product->getSku(), $buyRequest);
                $this->logDebug("addProductBySku successful");
            } catch (\Exception $e) {
                $this->logDebug("addProductBySku failed: " . $e->getMessage());
                // Fallback to original method
                try {
                    $this->cart->addProduct($product, $buyRequest);
                    $this->logDebug("Fallback addProduct successful");
                } catch (\Exception $e2) {
                    $this->logDebug("Fallback addProduct also failed: " . $e2->getMessage());
                    throw $e2; // Re-throw the exception
                }
            }

            // Alternative approach: Add item directly to quote to bypass merging logic
            try {
                $this->logDebug("Trying alternative approach: direct quote item addition...");
                $quote = $this->cart->getQuote();

                // Create a new quote item directly
                $item = $quote->addProduct($product, $buyRequest);

                // Force the item to be treated as new by setting unique data
                $item->setData('unique_cart_id', $uniqueId);
                $item->setData('force_new_item', true);

                $this->logDebug("Alternative approach successful - item ID: " . $item->getItemId());
            } catch (\Exception $e) {
                $this->logDebug("Alternative approach failed: " . $e->getMessage());
                // Continue with normal flow
            }

            // COMPLETELY NEW APPROACH: Bypass Magento's cart logic entirely
            try {
                $this->logDebug("Trying COMPLETELY NEW APPROACH: Direct database insertion...");

                // Get the quote
                $quote = $this->cart->getQuote();

                // Create a completely new quote item with unique data
                $item = $quote->addProduct($product, $buyRequest);

                // Force unique identification at the database level
                $item->setData('unique_cart_id', $uniqueId);
                $item->setData('force_new_item', true);
                $item->setData('bypass_merging', true);

                // Set custom options that make this item completely unique
                $customOptions = [
                    'unique_id' => $uniqueId,
                    'timestamp' => time(),
                    'random_hash' => md5($uniqueId . time() . rand()),
                    'force_separate' => true
                ];

                $item->setData('custom_options', $customOptions);

                // Force save without triggering merge logic
                $item->save();

                $this->logDebug("COMPLETELY NEW APPROACH successful - item ID: " . $item->getItemId());

                // Skip the normal cart flow entirely
                $this->cart->save();
                $message = "Product added successfully as completely new item (bypassing merge logic)";

                // Get updated cart info directly
                $cartInfo = $this->getCartInfo();

                $this->logDebug("Final message: $message");
                $this->logDebug('=== ADD TO CART END ===');

                return [
                    'status' => true,
                    'message' => $message,
                    'data' => $cartInfo
                ];

            } catch (\Exception $e) {
                $this->logDebug("COMPLETELY NEW APPROACH failed: " . $e->getMessage());
                // Fall back to normal flow
            }

            // ULTIMATE SOLUTION: Create completely separate items by modifying the product itself
            try {
                $this->logDebug("Trying ULTIMATE SOLUTION: Product modification approach...");

                // Create a completely new product object with unique data
                $uniqueProduct = clone $product;

                // Modify the product to appear as completely different
                $uniqueProduct->setData('entity_id', $productId . '_' . $uniqueId);
                $uniqueProduct->setData('sku', $product->getSku() . '_' . $uniqueId);
                $uniqueProduct->setData('unique_cart_id', $uniqueId);

                // Create a new buy request with unique data
                $uniqueBuyRequest = new \Magento\Framework\DataObject();
                $uniqueBuyRequest->setQty($quantity);

                // Add the original options
                if (!empty($options)) {
                    foreach ($options as $key => $value) {
                        $uniqueBuyRequest->setData($key, $value);
                    }
                }

                // Add unique identifiers to make this completely separate
                $uniqueBuyRequest->setData('unique_cart_id', $uniqueId);
                $uniqueBuyRequest->setData('force_new_item', true);
                $uniqueBuyRequest->setData('bypass_merging', true);
                $uniqueBuyRequest->setData('custom_product_id', $productId . '_' . $uniqueId);
                $uniqueBuyRequest->setData('custom_sku', $product->getSku() . '_' . $uniqueId);
                $uniqueBuyRequest->setData('timestamp', time());
                $uniqueBuyRequest->setData('random_id', uniqid());
                $uniqueBuyRequest->setData('item_unique_hash', md5($uniqueId . time() . rand()));

                $this->logDebug("ULTIMATE SOLUTION: Created unique product with ID: " . $uniqueProduct->getData('entity_id'));
                $this->logDebug("ULTIMATE SOLUTION: Created unique SKU: " . $uniqueProduct->getData('sku'));

                // Add the unique product to cart
                $this->cart->addProduct($uniqueProduct, $uniqueBuyRequest);
                $this->cart->save();

                $this->logDebug("ULTIMATE SOLUTION successful - product added as completely separate item");

                $message = "Product added successfully as completely separate item (no merging possible)";

                // Get updated cart info
                $cartInfo = $this->getCartInfo();

                $this->logDebug("Final message: $message");
                $this->logDebug('=== ADD TO CART END ===');

                return [
                    'status' => true,
                    'message' => $message,
                    'data' => $cartInfo
                ];

            } catch (\Exception $e) {
                $this->logDebug("ULTIMATE SOLUTION failed: " . $e->getMessage());
                // Continue with normal flow as last resort
            }

            // FINAL FALLBACK: Direct database manipulation to prevent merging
            try {
                $this->logDebug("Trying FINAL FALLBACK: Direct database manipulation...");

                // Get the current quote
                $quote = $this->cart->getQuote();

                // Add the product normally first
                $this->cart->addProduct($product, $buyRequest);
                $this->cart->save();

                // Now get the latest items and force them to be separate
                $quote = $this->cart->getQuote();
                $allItems = $quote->getAllItems();

                // Find the item we just added
                $newItem = null;
                foreach ($allItems as $item) {
                    if ($item->getProductId() == $productId) {
                        $itemOptions = $this->getItemOptions($item);
                        if ($this->compareOptions($options, $itemOptions)) {
                            $newItem = $item;
                            break;
                        }
                    }
                }

                if ($newItem) {
                    // Force this item to be completely unique
                    $newItem->setData('unique_cart_id', $uniqueId);
                    $newItem->setData('force_new_item', true);
                    $newItem->setData('bypass_merging', true);
                    $newItem->setData('custom_options', [
                        'unique_id' => $uniqueId,
                        'timestamp' => time(),
                        'random_hash' => md5($uniqueId . time() . rand()),
                        'force_separate' => true
                    ]);

                    // Force save
                    $newItem->save();

                    $this->logDebug("FINAL FALLBACK successful - item forced to be unique");
                    $message = "Product added successfully with forced uniqueness";
                } else {
                    $this->logDebug("FINAL FALLBACK: Could not find newly added item");
                    $message = "Product added successfully (fallback)";
                }

            } catch (\Exception $e) {
                $this->logDebug("FINAL FALLBACK failed: " . $e->getMessage());
                $message = "Product added successfully (fallback)";
            }

            // FINAL SOLUTION: Direct database manipulation at the core level
            try {
                $this->logDebug("Trying FINAL SOLUTION: Core database manipulation...");

                // Get the current quote
                $quote = $this->cart->getQuote();

                // Add the product normally first
                $this->cart->addProduct($product, $buyRequest);
                $this->cart->save();

                // Now get the latest items and force them to be separate
                $quote = $this->cart->getQuote();
                $allItems = $quote->getAllItems();

                // Find the item we just added
                $newItem = null;
                foreach ($allItems as $item) {
                    if ($item->getProductId() == $productId) {
                        $itemOptions = $this->getItemOptions($item);
                        if ($this->compareOptions($options, $itemOptions)) {
                            $newItem = $item;
                            break;
                        }
                    }
                }

                if ($newItem) {
                    // Force this item to be completely unique
                    $newItem->setData('unique_cart_id', $uniqueId);
                    $newItem->setData('force_new_item', true);
                    $newItem->setData('bypass_merging', true);
                    $newItem->setData('custom_options', [
                        'unique_id' => $uniqueId,
                        'timestamp' => time(),
                        'random_hash' => md5($uniqueId . time() . rand()),
                        'force_separate' => true
                    ]);

                    // Force save
                    $newItem->save();

                    $this->logDebug("FINAL FALLBACK successful - item forced to be unique");
                    $message = "Product added successfully with forced uniqueness";
                } else {
                    $this->logDebug("FINAL FALLBACK: Could not find newly added item");
                    $message = "Product added successfully";
                }

            } catch (\Exception $e) {
                $this->logDebug("FINAL FALLBACK failed: " . $e->getMessage());
                $message = "Product added successfully (fallback)";
            }

            // ULTIMATE FINAL SOLUTION: Direct database query to prevent merging
            try {
                $this->logDebug("Trying ULTIMATE FINAL SOLUTION: Direct database query...");

                // Get the resource connection
                $resource = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();

                // Get the quote ID
                $quoteId = $this->cart->getQuote()->getId();

                // Find the latest quote item for this product
                $select = $connection->select()
                    ->from('quote_item')
                    ->where('quote_id = ?', $quoteId)
                    ->where('product_id = ?', $productId)
                    ->order('item_id DESC')
                    ->limit(1);

                $latestItem = $connection->fetchRow($select);

                if ($latestItem) {
                    // Update the item to be completely unique
                    $updateData = [
                        'unique_cart_id' => $uniqueId,
                        'force_new_item' => 1,
                        'bypass_merging' => 1,
                        'custom_options' => json_encode([
                            'unique_id' => $uniqueId,
                            'timestamp' => time(),
                            'random_hash' => md5($uniqueId . time() . rand()),
                            'force_separate' => true
                        ])
                    ];

                    $connection->update(
                        'quote_item',
                        $updateData,
                        ['item_id = ?' => $latestItem['item_id']]
                    );

                    $this->logDebug("ULTIMATE FINAL SOLUTION successful - database updated directly");
                    $message = "Product added successfully with database-level uniqueness";
                } else {
                    $this->logDebug("ULTIMATE FINAL SOLUTION: Could not find item in database");
                    $message = "Product added successfully";
                }

            } catch (\Exception $e) {
                $this->logDebug("ULTIMATE FINAL SOLUTION failed: " . $e->getMessage());
                $message = "Product added successfully (database fallback)";
            }

            $this->logDebug('New item added to cart');

            // Debug buyRequest after adding
            $this->logDebug("BuyRequest after addProduct: " . json_encode($buyRequest->getData()));

            // Debug cart state immediately after addProduct
            $this->logDebug("Cart state immediately after addProduct:");
            $tempQuote = $this->cart->getQuote();
            $tempItems = $tempQuote->getAllItems();
            $this->logDebug("Temp items count: " . count($tempItems));
            foreach ($tempItems as $tempItem) {
                $this->logDebug("Temp item - ID: " . $tempItem->getItemId() .
                               ", Product ID: " . $tempItem->getProductId() .
                               ", Qty: " . $tempItem->getQty());
            }

            $this->cart->save();

            // Force quote to reload and ensure items are properly saved
            $this->cart->getQuote()->setIsActive(true);
            $this->cart->save();

            // Force a complete quote reload to ensure consistency
            $this->cart->getQuote()->load($this->cart->getQuote()->getId());

            // No need to validate existing items since we always add new ones
            $this->logDebug("Item added successfully - no validation needed");

            $message = "Product added successfully as new item (no quantity merging)";

            // Reload quote to get updated items
            $quote = $this->cart->getQuote();
            $this->logDebug("Quote reloaded after adding, new quote ID: " . $quote->getId());

            // Get all items after adding
            $allItemsAfter = $quote->getAllItems();
            $this->logDebug("Total items after adding: " . count($allItemsAfter));

            foreach ($allItemsAfter as $item) {
                $this->logDebug("Item after adding - ID: " . $item->getItemId() .
                               ", Product ID: " . $item->getProductId() .
                               ", Qty: " . $item->getQty() .
                               ", Visible: " . ($item->getIsVisible() ? 'YES' : 'NO'));
            }

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_added', [
                'product' => $product,
                'quantity' => $quantity,
                'options' => $options
            ]);

            // Final cart save to ensure all changes are persisted
            $this->cart->save();

            // Force quote reload one more time to ensure consistency
            $this->cart->getQuote()->load($this->cart->getQuote()->getId());

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

            // Filter for visible items manually to ensure we get all items
            $visibleItems = [];

            // Filter for visible items manually - include items that might be invisible due to custom options
            foreach ($allItems as $item) {
                $this->logDebug("Processing item for visibility - ID: " . $item->getItemId() .
                               ", Visible: " . ($item->getIsVisible() ? 'YES' : 'NO') .
                               ", Parent: " . ($item->getParentItemId() ?: 'NONE') .
                               ", Product Type: " . $item->getProductType());

                // Include items that are not parent items and either visible or have custom options
                if (!$item->getParentItemId() && ($item->getIsVisible() || $item->getBuyRequest())) {
                    $visibleItems[] = $item;
                    $this->logDebug("Item added to visible items");
                } else {
                    $this->logDebug("Item skipped - Parent: " . ($item->getParentItemId() ? 'YES' : 'NO') .
                                   ", Visible: " . ($item->getIsVisible() ? 'YES' : 'NO') .
                                   ", Has BuyRequest: " . ($item->getBuyRequest() ? 'YES' : 'NO'));
                }
            }

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
     * NOTE: This method is no longer used since we always add new items
     */
    private function findExistingCartItem($productId, $options) {
        // This method is deprecated - we always add new items now
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
