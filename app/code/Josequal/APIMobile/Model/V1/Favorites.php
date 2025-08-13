<?php
namespace Josequal\APIMobile\Model\V1;

class Favorites extends \Josequal\APIMobile\Model\AbstractModel
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
    protected $wishlistProvider;
    protected $wishlist;
    protected $currencyHelper;
    protected $imageBuilder;
    protected $stockState;

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

        $this->wishlistProvider = $this->objectManager->get('\Magento\Wishlist\Controller\WishlistProviderInterface');
        $this->wishlist = $this->objectManager->get('\Magento\Wishlist\Model\Wishlist');
        $this->currencyHelper = $this->objectManager->get('\Magento\Framework\Pricing\Helper\Data');
        $this->stockState = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $this->productModel = $this->objectManager->get('\Magento\Catalog\Model\Product');
        $this->dataObjectFactory = $this->objectManager->get('\Magento\Framework\DataObjectFactory');
        $this->imageBuilder = $this->objectManager->get('\Magento\Catalog\Block\Product\ImageBuilder');
    }

    function getCustomerFavorites() {
        $list = $this->getFavoriteList();
        $info = $this->successStatus('Favorite Items');
        $info['data'] = $list;
        return $info;
    }

    function getFavoriteList() {
        $customerId = $this->customerSession->getCustomerId();
        $wishlist = $this->wishlistProvider->getWishlist();
        $storeId = $this->storeManager->getStore()->getId();

        $list = [];
        $items = $this->wishlist->loadByCustomerId($customerId, true)->getItemCollection();
        $wishlistItems = [];
        if (count($items) > 0) {
            foreach ($items as $item) {
                $processedProduct = $this->processProduct($item);
                if ($processedProduct) {
                    $list[] = $processedProduct;
                }
            }
        }

        return $list;
    }

    //Product List Data
    public function processProduct($wishlist) {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $productId = $wishlist->getProductId();

            // Load product without triggering configurable extensions
            $product = $this->objectManager->create('\Magento\Catalog\Model\Product');
            $product->setStoreId($storeId);
            $product->load($productId);

            if (!$product->getId()) {
                return null;
            }

            // Check if product is configurable and handle it properly
            if ($product->getTypeId() === 'configurable') {
                try {
                    // For configurable products, try to get a simple child product
                    $typeInstance = $product->getTypeInstance();
                    if ($typeInstance && method_exists($typeInstance, 'getUsedProducts')) {
                        $childProducts = $typeInstance->getUsedProducts($product);
                        if (!empty($childProducts)) {
                            $product = reset($childProducts);
                        }
                    }
                } catch (\Exception $e) {
                    // If configurable product handling fails, continue with the original product
                    $this->logger->error('Error handling configurable product: ' . $e->getMessage());
                }
            }

            $quantity = $this->stockState->getStockItem($product->getId());

            // Get prices safely
            $regularPrice = 0;
            $finalPrice = 0;
            $minPrice = 0;

            try {
                $priceInfo = $product->getPriceInfo();
                if ($priceInfo) {
                    $regularPriceObj = $priceInfo->getPrice('regular_price');
                    $finalPriceObj = $priceInfo->getPrice('final_price');

                    if ($regularPriceObj) {
                        $regularPrice = $regularPriceObj->getValue();
                    }
                    if ($finalPriceObj) {
                        $finalPrice = $finalPriceObj->getValue();
                    }
                }
            } catch (\Exception $e) {
                // Fallback to direct price methods
                $regularPrice = $product->getPrice() ?: 0;
                $finalPrice = $product->getFinalPrice() ?: $regularPrice;
            }

            // Get min price safely
            try {
                $minPrice = $product->getMinPrice() ?: $finalPrice;
            } catch (\Exception $e) {
                $minPrice = $finalPrice;
            }

            $difference = $regularPrice - $finalPrice;
            $discountPercentage = 0;

            if ($regularPrice > 0) {
                $discountPercentage = round((100 * $difference) / $regularPrice);
            }

            return [
                'wishlist_id' => $wishlist->getWishlistItemId(),
                'wishlist_item_id' => $wishlist->getWishlistId(),
                'product_id' => $product->getId(),
                'name' => $product->getName() ?: '',
                'type' => $product->getTypeId(),
                'qty' => $quantity ? $quantity->getQty() : 0,
                'price' => $this->currencyHelper->currency($regularPrice, true, false),
                'special_price' => $this->currencyHelper->currency($finalPrice, true, false),
                'lowest_price' => $this->currencyHelper->currency($minPrice, true, false),
                'stock_status' => $product->isSaleable() && $product->isAvailable(),
                'review_summary' => $this->getReviewSummary($product),
                'image' => $this->getImage($product, 'category_page_list')->getImageUrl(),
                'has_discount' => $difference > 0,
                'discount' => $discountPercentage . '%',
                'is_favorite' => true
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error processing product in favorites: ' . $e->getMessage());
            return null;
        }
    }

    //Get Product image from cache
    private function getImage($product, $imageId, $attributes = []) {
        return $this->imageBuilder->setProduct($product)->setImageId($imageId)->setAttributes($attributes)->create();
    }

    //Get products Review Summary
    private function getReviewSummary($product) {
        try {
            $reviewFactory = $this->objectManager->get('\Magento\Review\Model\ReviewFactory');
            $reviewFactory->create()->getEntitySummary($product, $this->_getStoreId());

            $summary = $product->getRatingSummary()->getRatingSummary();
            $averageRating = round($summary * 0.05, 1);
            $data = [
                'count' => (int) ($product->getRatingSummary()->getReviewsCount() ?? 0),
                'summary' => (int) ($summary ?? 0), // out of 100
                'averageRating' => (int) $averageRating, // out of 5
            ];
            return $data;
        } catch (\Exception $e) {
            return [
                'count' => 0,
                'summary' => 0,
                'averageRating' => 0
            ];
        }
    }

    function addProductToFav($data) {
        if(!isset($data['product_id']) || !$data['product_id']) {
            return $this->errorStatus(["Product Id is required"]);
        }

        $customer = $this->customerSession->getCustomerId();
        $wishlist = $this->wishlistProvider->getWishlist();
        $product = $this->productModel->load($data['product_id']);

        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            return $this->errorStatus("product_not_available");
        }

        $buyRequest = $this->dataObjectFactory->create($data);

        $result = $wishlist->addNewItem($product, $buyRequest);

        if (is_string($result)) {
            throw new \Magento\Framework\Exception\LocalizedException($result);
        }
        $wishlist->save();

        $info = $this->successStatus('Product added to fav successfully');
        $info['data'] = $this->getFavoriteList();
        return $info;
    }

    function removeProductFromFav($data) {
        if(!isset($data['product_id']) || !$data['product_id']) {
            return $this->errorStatus(["Product Id is required"]);
        }

        $customer = $this->customerSession->getCustomerId();
        $wishlist = $this->wishlistProvider->getWishlist();
        $items = $wishlist->getItemCollection();
        foreach ($items as $item) {
            if ($item->getProductId() == $data['product_id']) {
                $item->delete();
                $wishlist->save();
                break;
            }
        }

        $info = $this->successStatus('Product removed from favorite successfully');
        $info['data'] = $this->getFavoriteList();
        return $info;
    }
}
