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

            if ($existingItem) {
                // Update quantity for existing item
                $newQty = $existingItem->getQty() + $quantity;
                $existingItem->setQty($newQty);
                $this->cartItemRepository->save($existingItem);

                $message = "Quantity updated for existing item";
            } else {
                // Add new item to cart
                $this->cart->addProduct($product, array_merge(['qty' => $quantity], $options));
                $this->cart->save();
                $message = "Product added successfully";
            }

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

                $items[] = [
                    'id' => (string)$item->getItemId(),
                    'product_id' => (string)$item->getProductId(),
                    'name' => $item->getName(),
                    'sku' => $item->getSku(),
                    'qty' => (int)$item->getQty(),
                    'price' => $this->formatPrice($item->getPrice()),
                    'row_total' => $this->formatPrice($item->getRowTotal()),
                    'image' => $this->getProductImageUrl($product),
                    'options' => $options
                ];
            }

            $totals = $this->getCartTotals($quote);

            return [
                'items' => $items,
                'cart_qty' => (int)$quote->getItemsQty(),
                'has_coupon' => $quote->getCouponCode() ? true : false,
                'coupon' => $quote->getCouponCode() ?: '',
                'totals' => $totals
            ];

        } catch (\Exception $e) {
            return [
                'items' => [],
                'cart_qty' => 0,
                'has_coupon' => false,
                'coupon' => '',
                'totals' => []
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
                $this->cartItemRepository->deleteById($itemId);
                $message = "Item removed successfully";
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
     * Prepare product options from request data
     */
    private function prepareProductOptions($data) {
        $options = [];

        if (isset($data['color'])) {
            $options['color'] = $data['color'];
        }

        if (isset($data['size'])) {
            $options['size'] = $data['size'];
        }

        // Add more options as needed
        if (isset($data['custom_options'])) {
            $options = array_merge($options, $data['custom_options']);
        }

        return $options;
    }

    /**
     * Find existing cart item with same product and options
     */
    private function findExistingCartItem($productId, $options) {
        $quote = $this->cart->getQuote();

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getProductId() == $productId) {
                $itemOptions = $this->getItemOptions($item);
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
     * Get item options
     */
    private function getItemOptions($item) {
        $options = [];

        if ($item->getBuyRequest()) {
            $buyRequest = $item->getBuyRequest();
            if ($buyRequest->getColor()) {
                $options['color'] = $buyRequest->getColor();
            }
            if ($buyRequest->getSize()) {
                $options['size'] = $buyRequest->getSize();
            }
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
