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
use Magento\Catalog\Api\Data\ProductInterface;
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

            // Check if product exists
            try {
                $product = $this->productModel->setStoreId($this->storeManager->getStore()->getId())->load($productId);
                if (!$product) {
                    return $this->errorStatus(['Product not exist'], 404);
                }
            } catch (\Exception $e) {
                return $this->errorStatus(['Product not found']);
            }

            // Simple approach - just add product with quantity
            $params = ['qty' => $quantity];

            $this->cart->addProduct($product, $params);
            $this->cart->save();

            // Dispatch event for cart modification
            $this->eventManager->dispatch('josequal_cart_item_added', [
                'product' => $product,
                'quantity' => $quantity
            ]);

            // Get updated cart info
            $cartInfo = $this->getCartDetails();

            return [
                'status' => true,
                'message' => 'Product added successfully',
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
            return $this->successStatus('Cart Details', $cartInfo);
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
            $items = [];

            foreach ($quote->getAllVisibleItems() as $item) {
                $product = $item->getProduct();

                $items[] = [
                    'id' => (string)$item->getItemId(),
                    'product_id' => (string)$item->getProductId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'qty' => (int)$item->getQty(),
                    'price' => $this->formatPrice($item->getPrice()),
                    'row_total' => $this->formatPrice($item->getRowTotal()),
                    'image' => $this->getProductImageUrl($product),
                    'has_options' => false,
                    'options_summary' => '',
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
     * Format price
     */
    private function formatPrice($price) {
        return $this->currencyHelper->currency($price, true, false);
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
     * Get product image URL
     */
    private function getProductImageUrl($product) {
        try {
            if ($product->getImage() && $product->getImage() != 'no_selection') {
                return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
