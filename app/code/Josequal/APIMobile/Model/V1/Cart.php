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
use Magento\Framework\DataObject;

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

    /**
     * إضافة منتج إلى السلة
     * الحل الجديد: كل مجموعة خيارات مختلفة تنشئ عنصراً منفصلاً
     */
    public function addToCart($data) {
        if (!isset($data['product_id'])) {
            return $this->errorStatus(["Product is required"]);
        }

        $params['qty'] = isset($data['quantity']) ? (int)$data['quantity'] : 1;

        // تجهيز خيارات المنتج
        $options = [];
        if (!empty($data['options']) && is_array($data['options'])) {
            $options = $data['options'];
        }
        if (!empty($data['color'])) {
            $options['color'] = $data['color'];
        }
        if (!empty($data['size'])) {
            $options['size'] = $data['size'];
        }

        // تطبيع الخيارات قبل عمل hash
        ksort($options);
        $optionsHash = md5(json_encode($options));

        try {
            $product = $this->productModel
                ->setStoreId($this->storeManager->getStore()->getId())
                ->load($data['product_id']);

            if (!$product || !$product->getId()) {
                return $this->errorStatus(["Product not found"], 404);
            }

            $quote = $this->checkoutSession->getQuote();

            // البحث عن نفس المنتج ونفس الخيارات
            $foundExactMatch = false;
            foreach ($quote->getAllItems() as $item) {
                // تجاهل العناصر المخفية أو المحذوفة
                if ($item->getParentItemId()) {
                    continue;
                }

                if ($item->getProduct()->getId() == $data['product_id']) {
                    $buyRequest = $item->getBuyRequest();
                    $existingOptions = [];

                    if ($buyRequest) {
                        if ($buyRequest->getData('options')) {
                            $existingOptions = $buyRequest->getData('options');
                        } elseif ($buyRequest->getData('super_attribute')) {
                            $existingOptions = $buyRequest->getData('super_attribute');
                        }
                    }

                    // تطبيع الخيارات قبل المقارنة
                    ksort($existingOptions);
                    $existingHash = md5(json_encode($existingOptions));

                    // Debug: طباعة الخيارات للمقارنة
                    error_log("Comparing options - New: " . json_encode($options) . " vs Existing: " . json_encode($existingOptions));
                    error_log("Hash comparison - New: " . $optionsHash . " vs Existing: " . $existingHash);

                    if ($existingHash === $optionsHash) {
                        // نفس المنتج ونفس الخيارات → دمج الكمية
                        $item->setQty($item->getQty() + $params['qty']);
                        $quote->save();
                        $foundExactMatch = true;
                        break;
                    }
                }
            }

            // إذا وجدنا تطابق تام، نرجع النتيجة
            if ($foundExactMatch) {
                return $this->successStatus('Quantity updated for existing item', [
                    'data' => $this->getCartDetails(),
                    'debug' => [
                        'action_taken' => 'Merged quantity',
                        'options_hash' => $optionsHash
                    ]
                ]);
            }

            // إذا لم نجد تطابق تام، نضيف كعنصر جديد
            // هذا يضمن أن كل مجموعة خيارات مختلفة تنشئ عنصراً منفصلاً

            // إنشاء BuyRequest object يحتوي على الخيارات
            $buyRequest = new DataObject([
                'product' => $data['product_id'],
                'qty' => $params['qty'],
                'options' => $options
            ]);

            // إضافة المنتج كعنصر جديد مع BuyRequest
            $this->cart->addProduct($product, $buyRequest);
            $this->cart->save();

            // تأكد من حفظ الخيارات بشكل صحيح
            $quote = $this->checkoutSession->getQuote();
            $quote->collectTotals();
            $quote->save();

            // تأكد من أن العنصر الجديد له معرف فريد
            $newItems = $quote->getAllItems();
            foreach ($newItems as $newItem) {
                if ($newItem->getProduct()->getId() == $data['product_id']) {
                    $buyRequest = $newItem->getBuyRequest();
                    $newItemOptions = $buyRequest ? ($buyRequest->getData('options') ?? []) : [];
                    $newItemHash = md5(json_encode($newItemOptions));

                    if ($newItemHash === $optionsHash) {
                        error_log("New item added with ID: " . $newItem->getItemId() . " and options: " . json_encode($newItemOptions));
                        break;
                    }
                }
            }

            // Debug: تأكد من عدد العناصر بعد الإضافة
            error_log("Items count after adding new item: " . count($newItems));

            return $this->successStatus('Product added successfully', [
                'data' => $this->getCartDetails(),
                'debug' => [
                    'action_taken' => 'Created new item',
                    'options_hash' => $optionsHash,
                    'saved_options' => $options,
                    'items_count' => count($newItems)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorStatus($e->getMessage());
        }
    }

    /**
     * الحصول على معلومات السلة
     */
    public function getCartInfo($data = []) {
        $info = $this->successStatus('Cart Details');
        $info['data'] = $this->getCartDetails();
        return $info;
    }

    /**
     * الحصول على تفاصيل السلة
     * الحل الجديد: عرض كل عنصر بشكل منفصل
     */
    public function getCartDetails() {
        $quote = $this->checkoutSession->getQuote();
        $quote->collectTotals();
        $quote->save();

        $list = [];
        // استخدم getAllItems بدلاً من getAllVisibleItems لرؤية جميع العناصر
        $items = $quote->getAllItems();

        // Debug logging
        error_log("getCartDetails: Found " . count($items) . " items in cart");

        // Display each item separately without grouping
        foreach ($items as $item) {
            // تجاهل العناصر المخفية أو المحذوفة
            if ($item->getParentItemId()) {
                continue;
            }

            error_log("Processing item " . $item->getItemId() . " with product_id " . $item->getProduct()->getId());
            $itemOptions = $item->getOptions();
            error_log("Item options: " . json_encode($itemOptions));

            // تأكد من أن كل عنصر منفصل
            $productData = $this->processProduct($item);
            $list[] = $productData;
        }

        // Debug: تأكد من عدد العناصر
        error_log("Total items in cart: " . count($list));

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

    /**
     * معالجة بيانات المنتج في السلة
     * الحل الجديد: عرض الخيارات بشكل واضح
     */
    public function processProduct($item) {
        $product = $item->getProduct();

        // Get options from item options
        $itemOptions = $this->formatCartOptions($item->getOptions());

        // Also get options from buy request if available
        $buyRequestOptions = [];
        try {
            $buyRequest = $item->getBuyRequest();
            if ($buyRequest && $buyRequest->getData('options')) {
                $buyRequestOptions = $buyRequest->getData('options');
                error_log("Buy request options for item " . $item->getItemId() . ": " . json_encode($buyRequestOptions));
            }
        } catch (\Exception $e) {
            // Continue without buy request
        }

        // If we have buy request options, use them to create formatted options
        if (!empty($buyRequestOptions)) {
            // تبسيط JSON الناتج - خلي الخيارات تطلع بشكل واضح
            $finalOptions = [
                'options' => $buyRequestOptions
            ];

            // إضافة الخيارات المحددة مباشرة
            if (isset($buyRequestOptions['color'])) {
                $finalOptions['color'] = $buyRequestOptions['color'];
            }
            if (isset($buyRequestOptions['size'])) {
                $finalOptions['size'] = $buyRequestOptions['size'];
            }
        } else {
            $finalOptions = $itemOptions;
        }

        // Debug: تأكد من الخيارات
        error_log("Final options for item " . $item->getItemId() . ": " . json_encode($finalOptions));

        $productData = [
            'id' => $item->getItemId(),
            'product_id' => $product->getId(),
            'name' => $item->getName(),
            'sku' => $item->getSku(),
            'qty' => $item->getQty(),
            'price' => $this->currencyHelper->currency($item->getPrice(), true, false),
            'row_total' => $this->currencyHelper->currency($item->getRowTotal(), true, false),
            'image' => $this->getImage($product, 'product_thumbnail_image'),
            'options' => $finalOptions
        ];

        return $productData;
    }

    /**
     * الحصول على صورة المنتج
     */
    public function getImage($product, $imageId, $attributes = []) {
        return $this->imageBuilder->setProduct($product)->setImageId($imageId)->setAttributes($attributes)->create();
    }

    /**
     * تنسيق خيارات السلة
     * الحل الجديد: معالجة أفضل للخيارات
     */
    private function formatCartOptions($options) {
        $formattedOptions = [];

        // Debug logging
        error_log("formatCartOptions: Input options: " . json_encode($options));

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

                    // Debug logging
                    error_log("Formatted option: " . json_encode($formattedOption));

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

        error_log("formatCartOptions: Final formatted options: " . json_encode($formattedOptions));
        return $formattedOptions;
    }

    /**
     * تحديث كمية عنصر في السلة
     */
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

    /**
     * حذف عنصر من السلة
     */
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
