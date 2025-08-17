<?php
namespace Josequal\APIMobile\Model\V1;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;

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
        $this->_checkoutSession = $this->objectManager->get('\Magento\Checkout\Model\Session');
        $this->stockState = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $this->currencyHelper = $this->objectManager->get('\Magento\Framework\Pricing\Helper\Data');
        $this->imageBuilder = $this->objectManager->get('\Magento\Catalog\Block\Product\ImageBuilder');
        $this->scopeConfig = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $this->customerSession = $this->objectManager->get('\Magento\Customer\Model\Session');
    }

    /**
     * Add product to cart - SIMPLE VERSION
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
                $product = $this->productModel->setStoreId($this->storeManager->getStore()->getId())->load($productId);
                if (!$product) {
                    return $this->errorStatus(['Product not exist'], 404);
                }
            } catch (\Exception $e) {
                return $this->errorStatus(['Product not found']);
            }

            // Check if product already exists in cart with same options
            $quote = $this->cart->getQuote();
            $existingItem = null;

            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId && !$item->getParentItemId()) {
                    // Check if options match
                    $itemOptions = $this->getItemOptions($item);
                    if ($this->compareOptions($options, $itemOptions)) {
                        $existingItem = $item;
                        break;
                    }
                }
            }

            if ($existingItem) {
                // Product already exists with same options - increase quantity
                $currentQty = (int)$existingItem->getQty();
                $newQty = $currentQty + $quantity;
                $existingItem->setQty($newQty);

                $message = "Product quantity increased from $currentQty to $newQty";
            } else {
                // Product doesn't exist or has different options - add new item
                $params = ['qty' => $quantity];

                // Add options to buy request
                if (!empty($options)) {
                    foreach ($options as $key => $value) {
                        $params[$key] = $value;
                    }
                }

                $this->cart->addProduct($product, $params);
                $message = "Product added successfully";
            }

            $this->cart->save();

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_added', [
                'product' => $product,
                'quantity' => $quantity,
                'existing_item' => $existingItem ? true : false,
                'options' => $options
            ]);

            // Get updated cart info
            $cartInfo = $this->getCartDetails();

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
            $cartInfo = $this->getCartDetails();
            return [
                'status' => true,
                'message' => 'Cart Details',
                'data' => $cartInfo
            ];
        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
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

            $quote = $this->cart->getQuote();
            $item = $quote->getItemById($itemId);

            if (!$item) {
                return $this->errorStatus(['Item not found']);
            }

            $item->setQty($newQty);
            $this->cart->save();

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_updated', [
                'item' => $item,
                'old_qty' => $item->getOrigData('qty'),
                'new_qty' => $newQty
            ]);

            $cartInfo = $this->getCartDetails();

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
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($itemId);

                if (!$item) {
                    return $this->errorStatus(['Item not found']);
                }

                $quote->removeItem($itemId);
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
                        $quote->removeItem($item->getItemId());
                    }
                }
                $message = "All items for product removed successfully";
            } else {
                return $this->errorStatus(['Item ID or Product ID is required']);
            }

            $this->cart->save();
            $cartInfo = $this->getCartDetails();

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
            $this->cart->truncate();
            $this->cart->save();

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_cleared');

            return [
                'status' => true,
                'message' => 'Cart cleared successfully',
                'data' => [
                    'items' => [],
                    'cart_qty' => 0,
                    'has_coupon' => false,
                    'coupon' => '',
                    'totals' => [],
                    'cart_id' => '',
                    'store_id' => 0
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorStatus([$e->getMessage()]);
        }
    }

    /**
     * Get cart details - SIMPLE VERSION
     */
    private function getCartDetails() {
        try {
            $quote = $this->cart->getQuote();

            if (!$quote || !$quote->getId()) {
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

            $items = [];
            $allItems = $quote->getAllItems();

            foreach ($allItems as $item) {
                if ($item->getParentItemId()) {
                    continue; // Skip child items
                }

                $product = $item->getProduct();
                if (!$product) {
                    continue;
                }

                // Get item options
                $itemOptions = $this->getItemOptions($item);
                $formattedOptions = [];

                if (!empty($itemOptions)) {
                    foreach ($itemOptions as $key => $value) {
                        $formattedOptions[] = [
                            'key' => $key,
                            'label' => $key,
                            'value' => $value
                        ];
                    }
                }

                $items[] = [
                    'id' => (string)$item->getItemId(),
                    'product_id' => (string)$item->getProductId(),
                    'name' => $item->getName() ?: 'Unknown Product',
                    'sku' => $item->getSku() ?: 'Unknown SKU',
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

            $result = [
                'items' => $items,
                'cart_qty' => (int)$quote->getItemsQty(),
                'has_coupon' => $quote->getCouponCode() ? true : false,
                'coupon' => $quote->getCouponCode() ?: '',
                'totals' => $totals,
                'cart_id' => (string)$quote->getId(),
                'store_id' => (int)$this->storeManager->getStore()->getId()
            ];

            return $result;

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
     * Get cart totals
     */
    private function getCartTotals($quote) {
        try {
            if (!$quote) {
                return [
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

            $totals = [];

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

            return $totals;

        } catch (\Exception $e) {
            return [
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
    }

    /**
     * Format price
     */
    private function formatPrice($price) {
        try {
            if ($price === null || $price === '' || $price === 0) {
                return '$0.00';
            }
            return $this->currencyHelper->currency($price, true, false);
        } catch (\Exception $e) {
            return '$0.00';
        }
    }

    /**
     * Check if product is available
     */
    private function isProductAvailable($product) {
        try {
            if (!$product) {
                return false;
            }
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
            if (!$product) {
                return [
                    'is_in_stock' => false,
                    'qty' => 0,
                    'min_qty' => 0,
                    'max_qty' => 0
                ];
            }

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
     * Get product image URL
     */
    private function getProductImageUrl($product) {
        try {
            if (!$product) {
                return '';
            }

            // Try to get image from different sources
            $imagePath = '';

            // Method 1: Try getImage()
            if ($product->getImage() && $product->getImage() != 'no_selection') {
                $imagePath = $product->getImage();
            }
            // Method 1.5: Try getData('image')
            elseif ($product->getData('image') && $product->getData('image') != 'no_selection') {
                $imagePath = $product->getData('image');
            }
            // Method 2: Try getSmallImage()
            elseif ($product->getSmallImage() && $product->getSmallImage() != 'no_selection') {
                $imagePath = $product->getSmallImage();
            }
            // Method 3: Try getThumbnail()
            elseif ($product->getThumbnail() && $product->getThumbnail() != 'no_selection') {
                $imagePath = $product->getThumbnail();
            }

            if ($imagePath) {
                $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $fullUrl = $baseUrl . 'catalog/product' . $imagePath;
                return $fullUrl;
            }

            // Method 4: Try using imageBuilder as fallback
            try {
                if ($this->imageBuilder) {
                    $image = $this->imageBuilder->create($product, 'product_base_image');
                    if ($image && $image->getImageUrl()) {
                        return $image->getImageUrl();
                    }
                }
            } catch (\Exception $e) {
                // Silent fail for imageBuilder
            }

            // Method 5: Try using getMediaGalleryEntries as final fallback
            try {
                $mediaGallery = $product->getMediaGalleryEntries();
                if ($mediaGallery && count($mediaGallery) > 0) {
                    $firstImage = $mediaGallery[0];
                    if ($firstImage && $firstImage->getFile()) {
                        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                        return $baseUrl . 'catalog/product' . $firstImage->getFile();
                    }
                }
            } catch (\Exception $e) {
                // Silent fail for mediaGallery
            }

            return '';

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Prepare product options from data
     */
    private function prepareProductOptions($data) {
        $options = [];

        // Handle simple options (color, size, etc.)
        if (isset($data['color'])) {
            $options['color'] = $data['color'];
        }
        if (isset($data['size'])) {
            $options['size'] = $data['size'];
        }

        // Handle complex options array
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $option) {
                if (isset($option['label']) && isset($option['value'])) {
                    $options[$option['label']] = $option['value'];
                }
            }
        }

        return $options;
    }

    /**
     * Get item options from Magento quote item
     */
    private function getItemOptions($item) {
        $options = [];
        if ($item->getProduct()) {
            $productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            if (isset($productOptions['options'])) {
                foreach ($productOptions['options'] as $option) {
                    if (isset($option['label']) && isset($option['value'])) {
                        $options[$option['label']] = $option['value'];
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
        if (count($options1) !== count($options2)) {
            return false;
        }
        foreach ($options1 as $key => $value) {
            if (!isset($options2[$key]) || $options2[$key] !== $value) {
                return false;
            }
        }
        return true;
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
}
