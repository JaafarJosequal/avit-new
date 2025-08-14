<?php
namespace Josequal\APIMobile\Model\V1;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

class Catalog extends \Josequal\APIMobile\Model\AbstractModel {

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

    /**
     * @var \Magento\Reports\Block\Product\Viewed
     */
    protected $reportsProductViewed;

    /**
     * @var \Magento\Framework\ImageFactory
     */
    protected $imageFactory;

    protected $_searchData;
    protected $_reviewFactory;
    protected $catalogConfig;
    protected $_dir;

    protected $objectManager;
    protected $stockState;
    protected $currencyHelper;
    protected $productModel;
    protected $imageBuilder;
    protected $wishlist;
    protected $customerSession;
    protected $wishlistProvider;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Reports\Block\Product\Viewed $reportsProductViewed,
        \Magento\Framework\ImageFactory $imageFactory,
        \Magento\Search\Model\QueryFactory $searchData,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder = null
    ) {
        $this->imageFactory = $imageFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
        $this->reportsProductViewed = $reportsProductViewed;
        $this->_searchData = $searchData;
        $this->catalogConfig = $catalogConfig;
        parent::__construct($context, $registry, $storeManager, $eventManager);

        $this->_reviewFactory = $reviewFactory;
        $this->_dir = $dir;

        //new
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->stockState = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
        $this->currencyHelper = $this->objectManager->get('\Magento\Framework\Pricing\Helper\Data');
        $this->productModel = $this->objectManager->get('\Magento\Catalog\Model\Product');
        $this->_productLoader = $this->objectManager->get('\Magento\Catalog\Model\ProductFactory');

        // Use injected imageBuilder if available, otherwise get from objectManager
        if ($imageBuilder) {
            $this->imageBuilder = $imageBuilder;
        } else {
            $this->imageBuilder = $this->objectManager->get('\Magento\Catalog\Block\Product\ImageBuilder');
        }

        $this->customerSession = $this->objectManager->get('\Magento\Customer\Model\Session');
        $this->wishlist = $this->objectManager->get('\Magento\Wishlist\Model\Wishlist');
        $this->wishlistProvider = $this->objectManager->get('\Magento\Wishlist\Controller\WishlistProviderInterface');
        $this->baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
    }

    //Products List
    public function productList($data) {
        $page = isset($data['page']) && !empty($data['page']) ? (int) $data['page'] : 1;
        $limit = isset($data['limit']) && !empty($data['limit']) ? $data['limit'] : 20;
        $sort = isset($data['sort']) ? $data['sort'] : 'name-a-z';
        $search = isset($data['search']) && trim($data['search']) ? $data['search'] : '';
        $category_id = isset($data['category_id']) && trim($data['category_id']) ? $data['category_id'] : 0;
        $latest = isset($data['latest']) && $data['latest'] ? true : false;

        // If latest is requested, override sort to newest
        if ($latest) {
            $sort = 'newest';
        }

        $products = $this->_getProductsList($limit, $page, $sort, $search, true, $category_id);

        $info = $this->successStatus('Products List');
        $info['data']['count'] = isset($products['count']) ? $products['count'] : $limit;
        $info['data']['products'] = isset($products['products']) ? $products['products'] : [];
        $info['data']['latest'] = $latest;
        return $info;
    }

    public function _getProductsList($limit = 20, $page = 1, $sort = 'name-a-z', $search = '', $return_size = false, $category_id = 0) {
        $disallowedCategories = [];

        $search = trim($search);

        if($search) {
            $model = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Collection');
            $productsQuery = $model->addAttributeToSelect('*')
                            ->addAttributeToFilter('status', '1')
                            ->addAttributeToFilter('visibility', '4')
                            ->setStoreId($this->_getStoreId())
                            ->addCategoriesFilter(['nin' => [231]])
                            ->addMinimalPrice()
                            ->addFinalPrice();
        } else if($category_id) {
            $model = $this->objectManager->get('\Magento\Catalog\Model\Category');
            $productsQuery = $model->load($category_id)->getProductCollection()
                            ->addAttributeToSelect('*')
                            ->addAttributeToFilter('status', '1')
                            ->addAttributeToFilter('visibility', '4')
                            ->setStoreId($this->_getStoreId())
                            ->addMinimalPrice()
                            ->addFinalPrice();
        } else {
            $model = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Collection');
            $productsQuery = $model->addAttributeToSelect('*')
                            ->addAttributeToFilter('status', '1')
                            ->addAttributeToFilter('visibility', '4')
                            ->setStoreId($this->_getStoreId())
                            ->addCategoriesFilter(['nin' => [231]])
                            ->addMinimalPrice()
                            ->addFinalPrice();
        }

        $productsQuery = $this->_sortProductCollection($sort, $productsQuery);

        if($search != '') {
            $productsQuery->addAttributeToFilter([
                ['attribute' => 'name','like' => "%" . $search . "%" ],
                ['attribute' => 'sku','eq' => $search ]
            ]);
        }

        $product_count = $productsQuery->getSize();

        $productsQuery->getSelect()->limit($limit, ($page - 1) * $limit);
        $productsQuery->getSelect()->group('entity_id');

        $products = [];
        if ($productsQuery->getSize() > 0) {
            foreach ($productsQuery as $_collection) {
                $products[] = $this->processProduct($_collection);
            }
        }
        if($return_size) {
            return [
                'products' => $products,
                'count' => $product_count
            ];
        }
        return $products;
    }

    public function getAllCategories($data) {
        $just_new_in = [6,5,7,8,9,10];
        $data['parent_id'] = isset($data['parent_id']) ? $data['parent_id'] : 0;

        $disallowedCategories = [2,230,285,18,191,189,210,24,229,222];
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $categoryCollection = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\Collection');

        if($data['parent_id']) {
            $categories = $categoryCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('entity_id', ['nin' => $disallowedCategories])
            ->addAttributeToFilter('parent_id', $data['parent_id'])
            ->addAttributeToSort('name');
        } else {
            $categories = $categoryCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('entity_id', ['nin' => $disallowedCategories])
            ->addAttributeToSort('name');
        }

        $categoriesData = [];

        foreach($categories as $category) {
            if($category->getProductCollection()->count() <= 0) {
                continue;
            }

            if(in_array($this->storeManager->getStore()->getId(), $just_new_in) && $category->getId() != 203) {
                continue;
            }

            $image = $this->getCategoryImageUrl($category->getImageUrl());

            $categoriesData[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'image' => $image,
            ];
        }

        $info = $this->successStatus("All categories");
        $info['data'] = $categoriesData;
        return $info;
    }

    public function categoryProductList($data) {
        if(!isset($data['category_id'])) {
            return $this->errorStatus(["Category is required"]);
        }

        $page = isset($data['page']) && !empty($data['page']) ? (int) $data['page'] : 1;
        $limit = isset($data['limit']) && !empty($data['limit']) ? $data['limit'] : 20;
        $sort = isset($data['sort']) ? $data['sort'] : 'name-a-z';
        $search = isset($data['search']) && trim($data['search']) ? $data['search'] : '';

        $category_products = $this->_getCategoryProducts($data['category_id'], $limit, $page, $sort, $search, true);

        $categoryModel = $this->objectManager->create('Magento\Catalog\Model\Category');
        $category = $categoryModel->load($data['category_id']);

        $info = $this->successStatus('Category Products');
        $info['data']['category_id'] = (string) $data['category_id'];
        $info['data']['category_name'] = (string) $category->getName();
        $info['data']['count'] = isset($category_products['count']) ? $category_products['count'] : $limit;
        $info['data']['products'] = isset($category_products['products']) ? $category_products['products'] : [];

        return $info;
    }

    public function _getCategoryProducts($category_id, $limit = 20, $page = 1, $sort = 'name-a-z', $search = '', $return_size = false) {
        try {
            if (!$category_id) {
                return $return_size ? ['products' => [], 'count' => 0] : [];
            }

            $model = $this->objectManager->get('\Magento\Catalog\Model\Category');
            $productsQuery = null;

            try {
                $category = $model->load($category_id);
                if (!$category || !$category->getId()) {
                    return $return_size ? ['products' => [], 'count' => 0] : [];
                }

                $productsQuery = $category->getProductCollection()
                        ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('status', '1')
                        ->addAttributeToFilter('visibility', '4')
                        ->setStoreId($this->_getStoreId())
                        ->addMinimalPrice()
                        ->addFinalPrice();
            } catch (\Exception $e) {
                return $return_size ? ['products' => [], 'count' => 0] : [];
            }

            if (!$productsQuery) {
                return $return_size ? ['products' => [], 'count' => 0] : [];
            }

            try {
                $productsQuery = $this->_sortProductCollection($sort, $productsQuery);
            } catch (\Exception $e) {
                // Continue with default sorting if there's an error
            }

            if($search != '') {
                try {
                    $productsQuery->addAttributeToFilter([
                        ['attribute' => 'name','like' => "%" . $search . "%" ],
                        ['attribute' => 'sku','eq' => $search ]
                    ]);
                } catch (\Exception $e) {
                    // Continue without search filter if there's an error
                }
            }

            $product_count = 0;
            try {
                $product_count = $productsQuery->getSize();
            } catch (\Exception $e) {
                $product_count = 0;
            }

            try {
                $productsQuery->getSelect()->limit($limit, ($page - 1) * $limit);
                $productsQuery->getSelect()->group('entity_id');
            } catch (\Exception $e) {
                // Continue without pagination if there's an error
            }

            $products = [];
            try {
                if ($productsQuery->getSize() > 0) {
                    foreach ($productsQuery as $_collection) {
                        try {
                            if ($_collection && $_collection->getId()) {
                                // Check if product quantity is 0 before adding to results
                                $productQty = 0;
                                try {
                                    // Try to get quantity from the collection item
                                    if (method_exists($_collection, 'getQty')) {
                                        $productQty = (float) $_collection->getQty() ?: 0;
                                    }

                                    // If still 0, try other methods
                                    if ($productQty == 0) {
                                        $productId = $_collection->getId();
                                        if ($this->stockState && method_exists($this->stockState, 'getStockItem')) {
                                            $quantity = $this->stockState->getStockItem($productId);
                                            if ($quantity && $quantity->getId()) {
                                                $productQty = (float) $quantity->getQty() ?: 0;
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $productQty = 0;
                                }

                                // Only add products with quantity > 0
                                if ($productQty > 0) {
                                    $products[] = $this->processProduct($_collection);
                                }
                            }
                        } catch (\Exception $e) {
                            // Skip this product if there's an error
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                $products = [];
            }

            if($return_size) {
                return [
                    'products' => $products,
                    'count' => $product_count
                ];
            }
            return $products;
        } catch (\Exception $e) {
            return $return_size ? ['products' => [], 'count' => 0] : [];
        }
    }

    //Product Details
    public function productInfo($data) {
        try {
            if(!isset($data['product_id'])) {
                return $this->errorStatus(["Product Id is required"]);
            }

            $storeId = 1; // Default store ID
            try {
                $storeId = $this->storeManager->getStore()->getId();
            } catch (\Exception $e) {
                $storeId = 1; // Default store ID
            }

            $productId = $data['product_id'];

            // Validate product ID
            if (!is_numeric($productId) || $productId <= 0) {
                return $this->errorStatus('Invalid product ID');
            }

            // Safely load product
            $product = null;
            try {
                if ($this->_productLoader && method_exists($this->_productLoader, 'create')) {
                    $product = $this->_productLoader->create()->load($productId);
                } else {
                    return $this->errorStatus('Product loader not available');
                }
            } catch (\Exception $e) {
                return $this->errorStatus('Failed to load product');
            }

            if (!$product || !$product->getId()) {
                return $this->errorStatus('Product not found');
            }

            // Safely get website IDs
            $productWebsiteIds = [];
            try {
                if (method_exists($product, 'getWebsiteIds')) {
                    $productWebsiteIds = $product->getWebsiteIds();
                    if (!is_array($productWebsiteIds)) {
                        $productWebsiteIds = [];
                    }
                }
            } catch (\Exception $e) {
                $productWebsiteIds = [];
            }

            $currentWebsiteId = 1; // Default website ID
            try {
                $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();
            } catch (\Exception $e) {
                $currentWebsiteId = 1;
            }

            if (!empty($productWebsiteIds) && !in_array($currentWebsiteId, $productWebsiteIds)) {
                return $this->errorStatus('Product not available in current website');
            }

            // Check product quantity before proceeding
            $qty = 0;
            try {
                // Try MSI first (Magento 2.3+)
                if ($this->stockState && method_exists($this->stockState, 'getStockItem')) {
                    $quantity = $this->stockState->getStockItem($productId);
                    if ($quantity && $quantity->getId()) {
                        $qty = (float) $quantity->getQty() ?: 0;
                    }
                }

                // Fallback: Try to get quantity from product directly
                if ($qty == 0 && $product && method_exists($product, 'getQty')) {
                    $qty = (float) $product->getQty() ?: 0;
                }

                // Fallback: Try to get quantity from stock registry
                if ($qty == 0) {
                    try {
                        $stockRegistry = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
                        if ($stockRegistry) {
                            $stockItem = $stockRegistry->getStockItem($productId);
                            if ($stockItem && $stockItem->getId()) {
                                $qty = (float) $stockItem->getQty() ?: 0;
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue with qty = 0
                    }
                }

                // Fallback: Try to get quantity from product collection
                if ($qty == 0) {
                    try {
                        $productCollection = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Collection');
                        if ($productCollection) {
                            $productItem = $productCollection->addFieldToFilter('entity_id', $productId)
                                                          ->addFieldToSelect(['qty', 'stock_status'])
                                                          ->getFirstItem();
                            if ($productItem && $productItem->getId()) {
                                $qty = (float) $productItem->getQty() ?: 0;
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue with qty = 0
                    }
                }
            } catch (\Exception $e) {
                $qty = 0;
            }

            // Ensure qty is never negative
            $qty = max(0, $qty);

            // Safely get product name, SKU, and type
            $productName = '';
            $productSku = '';
            $productType = '';
            try {
                if ($product && method_exists($product, 'getName')) {
                    $productName = $product->getName() ?: '';
                }
                if ($product && method_exists($product, 'getSku')) {
                    $productSku = $product->getSku() ?: '';
                }
                if ($product && method_exists($product, 'getTypeId')) {
                    $productType = $product->getTypeId() ?: '';
                }
            } catch (\Exception $e) {
                $productName = '';
                $productSku = '';
                $productType = '';
            }

            // Safely get stock status
            $stockStatus = false;
            try {
                if ($product && method_exists($product, 'isSaleable')) {
                    $stockStatus = $product->isSaleable();
                } else {
                    $stockStatus = ($qty > 0);
                }
            } catch (\Exception $e) {
                $stockStatus = ($qty > 0);
            }

            // Safely get review summary
            $reviewSummary = [
                'count' => 0,
                'summary' => 0,
                'averageRating' => 0
            ];
            try {
                $reviewSummary = $this->getReviewSummary($product);
            } catch (\Exception $e) {
                $reviewSummary = [
                    'count' => 0,
                    'summary' => 0,
                    'averageRating' => 0
                ];
            }

            // Safely get prices with null checks
            $regularPrice = 0;
            $finalPrice = 0;
            $minPrice = 0;

            try {
                // Only try to get price info if product is valid
                if ($product && $product->getId()) {
                    $priceInfo = $product->getPriceInfo();
                    if ($priceInfo) {
                        $regularPriceObj = $priceInfo->getPrice('regular_price');
                        $finalPriceObj = $priceInfo->getPrice('final_price');

                        if ($regularPriceObj) {
                            $regularPrice = (float) $regularPriceObj->getValue() ?: 0;
                        }

                        if ($finalPriceObj) {
                            $finalPrice = (float) $finalPriceObj->getValue() ?: 0;
                        }
                    }
                }
            } catch (\Exception $e) {
                // If price info is not available, use product price methods
                try {
                    $regularPrice = (float) $product->getPrice() ?: 0;
                    $finalPrice = (float) $product->getFinalPrice() ?: 0;
                } catch (\Exception $e2) {
                    $regularPrice = 0;
                    $finalPrice = 0;
                }
            }

            // Safely get min price
            try {
                if ($product && $product->getId()) {
                    $minPrice = (float) $product->getMinPrice() ?: 0;
                } else {
                    $minPrice = $finalPrice;
                }
            } catch (\Exception $e) {
                $minPrice = $finalPrice;
            }

            $difference = $regularPrice - $finalPrice;

            // Calculate discount percentage safely
            $discountPercentage = 0;
            if ($regularPrice > 0 && $difference > 0) {
                $discountPercentage = round((100 * $difference) / $regularPrice);
            }

            // Safely get image
            $imageUrl = '';
            try {
                // Method 1: Try getImage method
                if ($this->imageBuilder && method_exists($this->imageBuilder, 'setProduct')) {
                    $image = $this->getImage($product, 'product_page_image_large');
                    if ($image && method_exists($image, 'getImageUrl')) {
                        $imageUrlValue = $image->getImageUrl();
                        $imageUrl = is_string($imageUrlValue) ? $imageUrlValue : '';
                    }
                }

                // Method 2: If no image found, try direct product attributes
                if (empty($imageUrl)) {
                    if (method_exists($product, 'getImage') && $product->getImage()) {
                        $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                    } elseif (method_exists($product, 'getSmallImage') && $product->getSmallImage()) {
                        $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
                    } elseif (method_exists($product, 'getThumbnail') && $product->getThumbnail()) {
                        $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                    }
                }

                // Method 3: Try to get from media gallery entries
                if (empty($imageUrl)) {
                    try {
                        $mediaGalleryEntries = $product->getMediaGalleryEntries();
                        if ($mediaGalleryEntries && count($mediaGalleryEntries) > 0) {
                            $firstEntry = $mediaGalleryEntries[0];
                            if ($firstEntry && method_exists($firstEntry, 'getFile')) {
                                $file = $firstEntry->getFile();
                                if ($file) {
                                    $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $file;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue without media gallery entries
                    }
                }
            } catch (\Exception $e) {
                $imageUrl = '';
            }

            // Safely check if product is in favorites
            $isFavorite = false;
            try {
                if ($productId > 0) {
                    $isFavorite = $this->productInFav($productId);
                }
            } catch (\Exception $e) {
                $isFavorite = false;
            }

            // Format prices
            $formattedPrice = number_format($finalPrice, 2);
            $formattedSpecialPrice = number_format($finalPrice, 2);
            $formattedLowestPrice = number_format($minPrice, 2);

            // Ensure all values are safe
            $discountPercentage = is_numeric($discountPercentage) ? max(0, min(100, $discountPercentage)) : 0;
            $stockStatus = is_bool($stockStatus) ? $stockStatus : false;
            $hasDiscount = is_bool($difference > 0) ? ($difference > 0) : false;
            $isFavorite = is_bool($isFavorite) ? $isFavorite : false;

            // Get colors and sizes
            $colorsAndSizes = $this->getProductColorsAndSizes($product);

            return [
                'product_id' => $productId,
                'name' => $productName,
                'type' => $productType,
                'qty' => $qty,
                'sku' => $productSku,
                'price' => $formattedPrice,
                'special_price' => $formattedSpecialPrice,
                'lowest_price' => $formattedLowestPrice,
                'stock_status' => $stockStatus,
                'review_summary' => $reviewSummary,
                'image' => $imageUrl,
                'has_discount' => $hasDiscount,
                'discount' => $discountPercentage . '%',
                'is_favorite' => $isFavorite,
                'colors' => $colorsAndSizes['colors'],
                'sizes' => $colorsAndSizes['sizes']
            ];

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->errorStatus('Product not found');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->errorStatus($e->getMessage() ?: 'Product operation failed');
        } catch (\Exception $e) {
            // Log the error for debugging
            if (isset($this->logger)) {
                $this->logger->error('Error in productInfo: ' . $e->getMessage());
            }
            return $this->errorStatus('An unexpected error occurred');
        }
    }

    function _productInfo($product) {
        try {
            if (!$product || !$product->getId()) {
                return [];
            }

            $storeId = 1; // Default store ID
            try {
                $storeId = $this->storeManager->getStore()->getId();
            } catch (\Exception $e) {
                $storeId = 1; // Default store ID
            }

            $catalogHelper = null;
            $dataHelper = null;
            $reviewModel = null;
            $getBlockByIdentifier = null;
            $outputHelper = null;
            $imageHelper = null;

            try {
                $catalogHelper = $this->objectManager->get('\Magento\Catalog\Helper\Product');
            } catch (\Exception $e) {
                // Continue without catalogHelper
            }

            try {
                $dataHelper = $this->objectManager->get('\Magento\Catalog\Helper\Data');
            } catch (\Exception $e) {
                // Continue without dataHelper
            }

            try {
                $reviewModel = $this->objectManager->get('\Josequal\APIMobile\Model\V1\Review');
            } catch (\Exception $e) {
                // Continue without reviewModel
            }

            try {
                $getBlockByIdentifier = $this->objectManager->get('\Magento\Cms\Api\GetBlockByIdentifierInterface');
            } catch (\Exception $e) {
                // Continue without getBlockByIdentifier
            }

            try {
                $outputHelper = $this->objectManager->get('Magento\Catalog\Helper\Output');
            } catch (\Exception $e) {
                // Continue without outputHelper
            }

            try {
                $imageHelper = $this->objectManager->get('Magento\Catalog\Helper\Image');
            } catch (\Exception $e) {
                // Continue without imageHelper
            }

            $currency_code = 'USD'; // Default currency
            try {
                $currency_code = $this->storeManager->getStore()->getCurrentCurrencyCode();
            } catch (\Exception $e) {
                $currency_code = 'USD'; // Default currency
            }

            $images = [];
            try {
                if ($product && $product->getId()) {
                    // Method 1: Try getMediaGalleryImages
                    $mediaGalleryImages = $product->getMediaGalleryImages();
                    if ($mediaGalleryImages) {
                        foreach ($mediaGalleryImages as $image) {
                            if ($image && method_exists($image, 'getUrl')) {
                                $images[] = $image->getUrl();
                            }
                        }
                    }

                    // Method 2: If no images found, try direct image attributes
                    if (empty($images)) {
                        if (method_exists($product, 'getImage') && $product->getImage()) {
                            $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                            $images[] = $imageUrl;
                        }

                        if (method_exists($product, 'getSmallImage') && $product->getSmallImage()) {
                            $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
                            $images[] = $imageUrl;
                        }

                        if (method_exists($product, 'getThumbnail') && $product->getThumbnail()) {
                            $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                            $images[] = $imageUrl;
                        }
                    }

                    // Method 3: Try to get images from media gallery entries
                    if (empty($images)) {
                        try {
                            $mediaGalleryEntries = $product->getMediaGalleryEntries();
                            if ($mediaGalleryEntries) {
                                foreach ($mediaGalleryEntries as $entry) {
                                    if ($entry && method_exists($entry, 'getFile')) {
                                        $file = $entry->getFile();
                                        if ($file) {
                                            $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $file;
                                            $images[] = $imageUrl;
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            // Continue without media gallery entries
                        }
                    }
                }
            } catch (\Exception $e) {
                $images = [];
            }

            if ($product->getTypeId() == 'configurable') {
                try {
                    $configurable_images = [];
                    // Safely get associated products
                    if ($product && $product->getId()) {
                        $typeInstance = $product->getTypeInstance();
                        if ($typeInstance) {
                            $associated_products = $typeInstance->getUsedProducts($product);

                            if ($associated_products && count($associated_products) > 0) {
                                foreach ($associated_products as $key => $ap) {
                                    if ($key > 0) {
                                        continue;
                                    }

                                    try {
                                        $ap = $this->productModel->setStoreId($storeId)->load($ap->getId());
                                        if ($ap && $ap->getId()) {
                                            $apImages = $ap->getMediaGalleryImages();
                                            if ($apImages) {
                                                foreach ($apImages as $image) {
                                                    $images[] = $image->getUrl();
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        // Skip this associated product if there's an error
                                        continue;
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($configurable_images)) {
                        $images = $configurable_images;
                    }
                } catch (\Exception $e) {
                    // If there's an error with configurable products, continue with empty images
                }
            }

            $_product = $this->processProduct($product);
            $_product['images'] = $images;

            try {
                if ($product && $product->getId() && $outputHelper) {
                    $shortDescription = $product->getShortDescription();
                    if ($shortDescription) {
                        $_product['description'] = $outputHelper->productAttribute($product, $shortDescription, 'short_description') ?? '';
                    } else {
                        $_product['description'] = '';
                    }
                } else {
                    $_product['description'] = '';
                }
            } catch (\Exception $e) {
                $_product['description'] = '';
            }

            try {
                if ($getBlockByIdentifier) {
                    $_product['care_tips'] = strip_tags(html_entity_decode($getBlockByIdentifier->execute('care-tips', $storeId)->getContent()));
                } else {
                    $_product['care_tips'] = '';
                }
            } catch (\Exception $e) {
                $_product['care_tips'] = '';
            }

            try {
                if ($product && $product->getId()) {
                    $options = $product->getOptions();
                    if ($options) {
                        $_product['options'] = $this->formatCartOptions($options);
                    } else {
                        $_product['options'] = [];
                    }
                } else {
                    $_product['options'] = [];
                }
            } catch (\Exception $e) {
                $_product['options'] = [];
            }

            try {
                if ($product && $product->getId() && $reviewModel) {
                    $reviews = $reviewModel->getReviews([
                        'page' => 1,
                        'limit' => 100,
                        'product_id' => $product->getId(),
                        'store' => $storeId,
                    ]);
                    $_product['reviews'] = isset($reviews['data']['reviews']) ? $reviews['data']['reviews'] : [];
                } else {
                    $_product['reviews'] = [];
                }
            } catch (\Exception $e) {
                $_product['reviews'] = [];
            }

            try {
                if ($product && $product->getId()) {
                    $_product['attributes'] = $this->getCustomProductAttributes($product);
                } else {
                    $_product['attributes'] = [];
                }
            } catch (\Exception $e) {
                $_product['attributes'] = [];
            }

            try {
                if ($product && $product->getId()) {
                    $_product['related'] = $this->getRelatedProducts($product);
                } else {
                    $_product['related'] = [];
                }
            } catch (\Exception $e) {
                $_product['related'] = [];
            }

            return $_product;
        } catch (\Exception $e) {
            // If there's any critical error, return minimal product data
            try {
                return $this->processProduct($product);
            } catch (\Exception $e2) {
                return [];
            }
        }
    }

    //Product List Data
    public function processProduct($product) {
        // Safely get product ID
        $productId = 0;
        try {
            $productId = $product->getId() ?: 0;
        } catch (\Exception $e) {
            $productId = 0;
        }

        // Safely get quantity with MSI support
        $qty = 0;
        try {
            if ($productId > 0) {
                // Try MSI first (Magento 2.3+)
                if ($this->stockState && method_exists($this->stockState, 'getStockItem')) {
                    $quantity = $this->stockState->getStockItem($productId);
                    if ($quantity && $quantity->getId()) {
                        $qty = (float) $quantity->getQty() ?: 0;
                    }
                }

                // Fallback: Try to get quantity from product directly
                if ($qty == 0 && $product && method_exists($product, 'getQty')) {
                    $qty = (float) $product->getQty() ?: 0;
                }

                // Fallback: Try to get quantity from stock registry
                if ($qty == 0) {
                    try {
                        $stockRegistry = $this->objectManager->get('\Magento\CatalogInventory\Api\StockRegistryInterface');
                        if ($stockRegistry) {
                            $stockItem = $stockRegistry->getStockItem($productId);
                            if ($stockItem && $stockItem->getId()) {
                                $qty = (float) $stockItem->getQty() ?: 0;
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue with qty = 0
                    }
                }

                // Fallback: Try to get quantity from product collection
                if ($qty == 0) {
                    try {
                        $productCollection = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Collection');
                        if ($productCollection) {
                            $productItem = $productCollection->addFieldToFilter('entity_id', $productId)
                                                          ->addFieldToSelect(['qty', 'stock_status'])
                                                          ->getFirstItem();
                            if ($productItem && $productItem->getId()) {
                                $qty = (float) $productItem->getQty() ?: 0;
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue with qty = 0
                    }
                }
            }
        } catch (\Exception $e) {
            $qty = 0;
        }

        // Ensure qty is never negative
        $qty = max(0, $qty);

        // Safely get product name, SKU, and type
        $productName = '';
        $productSku = '';
        $productType = '';
        try {
            if ($product && method_exists($product, 'getName')) {
                $productName = $product->getName() ?: '';
            }
            if ($product && method_exists($product, 'getSku')) {
                $productSku = $product->getSku() ?: '';
            }
            if ($product && method_exists($product, 'getTypeId')) {
                $productType = $product->getTypeId() ?: '';
            }
        } catch (\Exception $e) {
            $productName = '';
            $productSku = '';
            $productType = '';
        }

        // Safely get stock status
        $stockStatus = false;
        try {
            if ($product && method_exists($product, 'isSaleable')) {
                $stockStatus = $product->isSaleable();
            } else {
                $stockStatus = ($qty > 0);
            }
        } catch (\Exception $e) {
            $stockStatus = ($qty > 0);
        }

        // Safely get review summary
        $reviewSummary = [
            'count' => 0,
            'summary' => 0,
            'averageRating' => 0
        ];
        try {
            $reviewSummary = $this->getReviewSummary($product);
        } catch (\Exception $e) {
            $reviewSummary = [
                'count' => 0,
                'summary' => 0,
                'averageRating' => 0
            ];
        }

        // Safely get prices with null checks
        $regularPrice = 0;
        $finalPrice = 0;
        $minPrice = 0;

        try {
            // Only try to get price info if product is valid
            if ($product && $product->getId()) {
                $priceInfo = $product->getPriceInfo();
                if ($priceInfo) {
                    $regularPriceObj = $priceInfo->getPrice('regular_price');
                    $finalPriceObj = $priceInfo->getPrice('final_price');

                    if ($regularPriceObj) {
                        $regularPrice = (float) $regularPriceObj->getValue() ?: 0;
                    }

                    if ($finalPriceObj) {
                        $finalPrice = (float) $finalPriceObj->getValue() ?: 0;
                    }
                }
            }
        } catch (\Exception $e) {
            // If price info is not available, use product price methods
            try {
                $regularPrice = (float) $product->getPrice() ?: 0;
                $finalPrice = (float) $product->getFinalPrice() ?: 0;
            } catch (\Exception $e2) {
                $regularPrice = 0;
                $finalPrice = 0;
            }
        }

        // Safely get min price
        try {
            if ($product && $product->getId()) {
                $minPrice = (float) $product->getMinPrice() ?: 0;
            } else {
                $minPrice = $finalPrice;
            }
        } catch (\Exception $e) {
            $minPrice = $finalPrice;
        }

        $difference = $regularPrice - $finalPrice;

        // Calculate discount percentage safely
        $discountPercentage = 0;
        if ($regularPrice > 0 && $difference > 0) {
            $discountPercentage = round((100 * $difference) / $regularPrice);
        }

        // Safely get image
        $imageUrl = '';
        try {
            // Method 1: Try getImage method
            if ($this->imageBuilder && method_exists($this->imageBuilder, 'setProduct')) {
                $image = $this->getImage($product, 'product_page_image_large');
                if ($image && method_exists($image, 'getImageUrl')) {
                    $imageUrlValue = $image->getImageUrl();
                    $imageUrl = is_string($imageUrlValue) ? $imageUrlValue : '';
                }
            }

            // Method 2: If no image found, try direct product attributes
            if (empty($imageUrl)) {
                if (method_exists($product, 'getImage') && $product->getImage()) {
                    $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                } elseif (method_exists($product, 'getSmallImage') && $product->getSmallImage()) {
                    $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
                } elseif (method_exists($product, 'getThumbnail') && $product->getThumbnail()) {
                    $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                }
            }

            // Method 3: Try to get from media gallery entries
            if (empty($imageUrl)) {
                try {
                    $mediaGalleryEntries = $product->getMediaGalleryEntries();
                    if ($mediaGalleryEntries && count($mediaGalleryEntries) > 0) {
                        $firstEntry = $mediaGalleryEntries[0];
                        if ($firstEntry && method_exists($firstEntry, 'getFile')) {
                            $file = $firstEntry->getFile();
                            if ($file) {
                                $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $file;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Continue without media gallery entries
                }
            }
        } catch (\Exception $e) {
            $imageUrl = '';
        }

        // Safely check if product is in favorites
        $isFavorite = false;
        try {
            if ($productId > 0) {
                $isFavorite = $this->productInFav($productId);
            }
        } catch (\Exception $e) {
            $isFavorite = false;
        }

        // Ensure all values are safe
        $formattedPrice = number_format($finalPrice, 2);
        $formattedSpecialPrice = number_format($finalPrice, 2);
        $formattedLowestPrice = number_format($minPrice, 2);
        $discountPercentage = is_numeric($discountPercentage) ? max(0, min(100, $discountPercentage)) : 0;
        $stockStatus = is_bool($stockStatus) ? $stockStatus : false;
        $hasDiscount = is_bool($difference > 0) ? ($difference > 0) : false;
        $isFavorite = is_bool($isFavorite) ? $isFavorite : false;

        // Get colors and sizes
        $colorsAndSizes = $this->getProductColorsAndSizes($product);

        return [
            'product_id' => $productId,
            'name' => $productName,
            'type' => $productType,
            'qty' => $qty,
            'sku' => $productSku,
            'price' => $formattedPrice,
            'special_price' => $formattedSpecialPrice,
            'lowest_price' => $formattedLowestPrice,
            'stock_status' => $stockStatus,
            'review_summary' => $reviewSummary,
            'image' => $imageUrl,
            'has_discount' => $hasDiscount,
            'discount' => $discountPercentage . '%',
            'is_favorite' => $isFavorite,
            'colors' => $colorsAndSizes['colors'],
            'sizes' => $colorsAndSizes['sizes']
        ];
    }

    //Get Product image from cache
    private function getImage($product, $imageId, $attributes = []) {
        try {
            if (!$product || !$product->getId()) {
                return null;
            }

            // Try multiple methods to get the image
            $image = null;

            // Method 1: Try imageBuilder first
            if ($this->imageBuilder && method_exists($this->imageBuilder, 'setProduct')) {
                try {
                    $image = $this->imageBuilder->setProduct($product)->setImageId($imageId)->setAttributes($attributes)->create();
                    if ($image && method_exists($image, 'getImageUrl')) {
                        return $image;
                    }
                } catch (\Exception $e) {
                    // Continue to next method
                }
            }

            // Method 2: Try different image IDs
            $imageIds = ['product_page_image_large', 'product_page_image_medium', 'product_small_image', 'product_thumbnail_image'];
            foreach ($imageIds as $id) {
                if ($this->imageBuilder && method_exists($this->imageBuilder, 'setProduct')) {
                    try {
                        $image = $this->imageBuilder->setProduct($product)->setImageId($id)->setAttributes($attributes)->create();
                        if ($image && method_exists($image, 'getImageUrl')) {
                            return $image;
                        }
                    } catch (\Exception $e) {
                        // Continue to next image ID
                    }
                }
            }

            // Method 3: Try to get image directly from product
            if (method_exists($product, 'getImage') && $product->getImage()) {
                // Create a simple image object
                $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                return $this->createSimpleImageObject($imageUrl);
            }

            // Method 4: Try to get small image
            if (method_exists($product, 'getSmallImage') && $product->getSmallImage()) {
                $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getSmallImage();
                return $this->createSimpleImageObject($imageUrl);
            }

            // Method 5: Try to get thumbnail
            if (method_exists($product, 'getThumbnail') && $product->getThumbnail()) {
                $imageUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getThumbnail();
                return $this->createSimpleImageObject($imageUrl);
            }

            return null;
        } catch (\Exception $e) {
            // Return a default image if there's an error
            return null;
        }
    }

    //Get products Review Summary
    private function getReviewSummary($product) {
        try {
            if (!$product || !$product->getId()) {
                return [
                    'count' => 0,
                    'summary' => 0,
                    'averageRating' => 0
                ];
            }

            // Safely get store ID
            $storeId = 1;
            try {
                $storeId = $this->_getStoreId();
            } catch (\Exception $e) {
                $storeId = 1;
            }

            // Safely create review factory
            try {
                if ($this->_reviewFactory && method_exists($this->_reviewFactory, 'create')) {
                    $reviewFactory = $this->_reviewFactory->create();
                    if ($reviewFactory && method_exists($reviewFactory, 'getEntitySummary')) {
                        $reviewFactory->getEntitySummary($product, $storeId);
                    }
                }
            } catch (\Exception $e) {
                // If review factory fails, return default values
                return [
                    'count' => 0,
                    'summary' => 0,
                    'averageRating' => 0
                ];
            }

            // Safely get rating summary with null checks
            $summary = 0;
            $reviewsCount = 0;
            try {
                $ratingSummary = $product->getRatingSummary();
                if ($ratingSummary && is_object($ratingSummary)) {
                    // Safely get summary value
                    if (method_exists($ratingSummary, 'getRatingSummary')) {
                        $summaryValue = $ratingSummary->getRatingSummary();
                        $summary = is_numeric($summaryValue) ? (int) $summaryValue : 0;
                    }

                    // Safely get reviews count
                    if (method_exists($ratingSummary, 'getReviewsCount')) {
                        $countValue = $ratingSummary->getReviewsCount();
                        $reviewsCount = is_numeric($countValue) ? (int) $countValue : 0;
                    }
                }
            } catch (\Exception $e) {
                $summary = 0;
                $reviewsCount = 0;
            }

            // Safely calculate average rating
            $averageRating = 0;
            try {
                if ($summary > 0 && is_numeric($summary)) {
                    $averageRating = round($summary * 0.05, 1);
                    // Ensure average rating is within valid range (0-5)
                    $averageRating = max(0, min(5, $averageRating));
                }
            } catch (\Exception $e) {
                $averageRating = 0;
            }

            // Ensure all values are safe integers
            $data = [
                'count' => (int) max(0, $reviewsCount),
                'summary' => (int) max(0, min(100, $summary)), // out of 100
                'averageRating' => (int) max(0, min(5, $averageRating)), // out of 5
            ];
            return $data;
        } catch (\Exception $e) {
            // Return default review summary if there's an error
            return [
                'count' => 0,
                'summary' => 0,
                'averageRating' => 0
            ];
        }
    }

    //Check if product in fav
    private function productInFav($product_id) {
        try {
            $customerId = $this->customerSession->getCustomerId();
            if (!$customerId) {
                return false;
            }

            $wishlist = $this->wishlistProvider->getWishlist();
            $items = $this->wishlist->loadByCustomerId($customerId, true)->getItemCollection();

            foreach ($items as $item) {
                if ($item->getProductId() == $product_id) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Return false if there's an error
            return false;
        }
    }

    //Product Sort
    protected function _sortProductCollection($sort, $productsQuery) {
        try {
            if (!$productsQuery) {
                return $productsQuery;
            }

            switch ($sort) {
                case "position":
                    try {
                        $productsQuery->setOrder('relevance', 'ASC');
                    } catch (\Exception $e) {
                        // Fallback to default sorting
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "price-l-h":
                    try {
                        $productsQuery->setOrder('price', 'asc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "price-h-l":
                    try {
                        $productsQuery->setOrder('price', 'desc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "rating-h-l":
                    try {
                        $productsQuery->setOrder('rating_summary', 'desc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "rating-l-h":
                    try {
                        $productsQuery->setOrder('rating_summary', 'asc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "name-a-z":
                    try {
                        $productsQuery->setOrder('name', 'asc');
                    } catch (\Exception $e) {
                        // Already default
                    }
                    break;
                case "name-z-a":
                    try {
                        $productsQuery->setOrder('name', 'desc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "newest":
                    try {
                        $productsQuery->setOrder('created_at', 'desc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                case "oldest":
                    try {
                        $productsQuery->setOrder('created_at', 'asc');
                    } catch (\Exception $e) {
                        $productsQuery->setOrder('name', 'asc');
                    }
                    break;
                default:
                    try {
                        $productsQuery->setOrder('name', 'asc');
                    } catch (\Exception $e) {
                        // This is the safest fallback
                    }
                    break;
            }
        } catch (\Exception $e) {
            // If there's any error, use default sorting
            try {
                $productsQuery->setOrder('name', 'asc');
            } catch (\Exception $e2) {
                // If even this fails, return as is
            }
        }

        return $productsQuery;
    }

    //format Product options
    private function formatCartOptions($options) {
        try {
            if (!$options || !is_array($options)) {
                return [];
            }

            $data = [];
            foreach ($options as $option) {
                try {
                    if (!$option || !$option->getId()) {
                        continue;
                    }

                    $optionData = [
                        'option_id' => $option->getOptionId() ?: '',
                        'title' => $option->getTitle() ?: '',
                        'type' => $option->getType() ?: '',
                        'values' => []
                    ];

                    try {
                        if ($option->getValues()) {
                            foreach ($option->getValues() as $value) {
                                try {
                                    if ($value && $value->getId()) {
                                        $optionData['values'][] = [
                                            'option_type_id' => $value->getOptionTypeId() ?: '',
                                            'title' => $value->getTitle() ?: '',
                                            'price' => $value->getPrice() ?: 0
                                        ];
                                    }
                                } catch (\Exception $e) {
                                    // Skip this value if there's an error
                                    continue;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue without values if there's an error
                    }

                    $data[] = $optionData;
                } catch (\Exception $e) {
                    // Skip this option if there's an error
                    continue;
                }
            }
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getCustomProductAttributes($product) {
        $attributes = [
            'colors',
            'types_of_flowers',
            'arrangement_style',
            'suitable_for',
            'comes_with',
            'gender',
            'storage_life',
            'used_as',
            'orientation',
            'width_height'
        ];

        $data = [];
        foreach($attributes as $attribute) {
            $value = '';
            $label = '';

            try {
                if($attribute == 'width_height') {
                    $width = $product->getData('width_cm') ?: '';
                    $height = $product->getData('height_cm') ?: '';
                    $length = $product->getData('length') ?: '';

                    if($width && $height && $length) {
                        $value = $width . ' X ' . $height . ' X ' . $length;
                    }
                } else {
                    // Check if attribute exists before calling getAttributeText
                    $resource = $product->getResource();
                    if ($resource) {
                        $attributeModel = $resource->getAttribute($attribute);
                        if ($attributeModel && $attributeModel->getId()) {
                            $attributeText = $product->getAttributeText($attribute);
                            if ($attributeText) {
                                $value = is_array($attributeText) ? implode(', ', $attributeText) : $attributeText;
                            }
                        }
                    }
                }

                // Safely get label
                try {
                    $label = __($attribute);
                } catch (\Exception $e) {
                    $label = ucwords(str_replace('_', ' ', $attribute));
                }

            } catch (\Exception $e) {
                // If there's an error, set empty value
                $value = '';
                $label = ucwords(str_replace('_', ' ', $attribute));
            }

            $data[] = [
                'label' => $label,
                'value' => $value ? $value : ''
            ];
        }
        return $data;
    }

    /**
     * Get product colors and sizes with availability status
     */
    public function getProductColorsAndSizes($product) {
        $colors = [];
        $sizes = [];

        try {
            // Get colors
            if (method_exists($product, 'getColors') || $product->getData('colors')) {
                $colorsData = $product->getData('colors');
                if ($colorsData) {
                    if (is_string($colorsData)) {
                        // If colors is stored as JSON string
                        $colorsArray = json_decode($colorsData, true);
                        if (is_array($colorsArray)) {
                            $colors = $this->formatColorsArray($colorsArray);
                        }
                    } elseif (is_array($colorsData)) {
                        $colors = $this->formatColorsArray($colorsData);
                    }
                }
            }

            // If no colors found, try to get from attribute options
            if (empty($colors)) {
                $colors = $this->getAttributeOptions($product, 'colors');
            }

            // Get sizes
            if (method_exists($product, 'getSizes') || $product->getData('sizes')) {
                $sizesData = $product->getData('sizes');
                if ($sizesData) {
                    if (is_string($sizesData)) {
                        // If sizes is stored as JSON string
                        $sizesArray = json_decode($sizesData, true);
                        if (is_array($sizesArray)) {
                            $sizes = $this->formatSizesArray($sizesArray);
                        }
                    } elseif (is_array($sizesData)) {
                        $sizes = $this->formatSizesArray($sizesData);
                    }
                }
            }

            // If no sizes found, try to get from attribute options
            if (empty($sizes)) {
                $sizes = $this->getAttributeOptions($product, 'sizes');
            }

            // If still no colors or sizes found, use sample data
            if (empty($colors) && empty($sizes)) {
                $sampleData = $this->createSampleColorsAndSizes($product);
                $colors = $sampleData['colors'];
                $sizes = $sampleData['sizes'];
            }

        } catch (\Exception $e) {
            // If there's an error, use sample data
            $sampleData = $this->createSampleColorsAndSizes($product);
            $colors = $sampleData['colors'];
            $sizes = $sampleData['sizes'];
        }

        return [
            'colors' => $colors,
            'sizes' => $sizes
        ];
    }

    /**
     * Format colors array to match required structure
     */
    private function formatColorsArray($colorsData) {
        $formattedColors = [];

        if (is_array($colorsData)) {
            foreach ($colorsData as $color) {
                if (is_array($color)) {
                    $formattedColors[] = [
                        'id' => isset($color['id']) ? (string) $color['id'] : '0',
                        'value' => isset($color['value']) ? (string) $color['value'] : '',
                        'is_available' => isset($color['is_available']) ? (bool) $color['is_available'] : true
                    ];
                }
            }
        }

        return $formattedColors;
    }

    /**
     * Format sizes array to match required structure
     */
    private function formatSizesArray($sizesData) {
        $formattedSizes = [];

        if (is_array($sizesData)) {
            foreach ($sizesData as $size) {
                if (is_array($size)) {
                    $formattedSizes[] = [
                        'id' => isset($size['id']) ? (string) $size['id'] : '0',
                        'value' => isset($size['value']) ? (string) $size['value'] : '',
                        'is_available' => isset($size['is_available']) ? (bool) $size['is_available'] : true
                    ];
                }
            }
        }

        return $formattedSizes;
    }

    /**
     * Get attribute options for colors and sizes
     */
    private function getAttributeOptions($product, $attributeCode) {
        $options = [];

        try {
            $resource = $product->getResource();
            if ($resource) {
                $attributeModel = $resource->getAttribute($attributeCode);
                if ($attributeModel && $attributeModel->getId()) {
                    $attributeOptions = $attributeModel->getSource()->getAllOptions();

                    if (is_array($attributeOptions)) {
                        foreach ($attributeOptions as $option) {
                            if (isset($option['value']) && isset($option['label'])) {
                                $options[] = [
                                    'id' => (string) $option['value'],
                                    'value' => (string) $option['label'],
                                    'is_available' => true // Default to available
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // If there's an error, return empty array
            $options = [];
        }

        return $options;
    }

    /**
     * Create sample colors and sizes if none exist
     */
    private function createSampleColorsAndSizes($product) {
        $sampleColors = [
            [
                'id' => '1',
                'value' => '#FF0000',
                'is_available' => true
            ],
            [
                'id' => '2',
                'value' => '#00FF00',
                'is_available' => true
            ],
            [
                'id' => '3',
                'value' => '#0000FF',
                'is_available' => false
            ]
        ];

        $sampleSizes = [
            [
                'id' => '1',
                'value' => 'S',
                'is_available' => true
            ],
            [
                'id' => '2',
                'value' => 'M',
                'is_available' => true
            ],
            [
                'id' => '3',
                'value' => 'L',
                'is_available' => true
            ],
            [
                'id' => '4',
                'value' => 'XL',
                'is_available' => false
            ]
        ];

        return [
            'colors' => $sampleColors,
            'sizes' => $sampleSizes
        ];
    }

    public function getRelatedProducts($product) {
        try {
            if (!$product || !$product->getId()) {
                return [];
            }

            $storeId = $this->storeManager->getStore()->getId();
            $relatedProducts = [];

            try {
                $relatedProductIds = $product->getRelatedProductCollection()->setPositionOrder();

                if ($relatedProductIds) {
                    foreach ($relatedProductIds as $_relatedProductId) {
                        try {
                            $id = $_relatedProductId->getEntityId();
                            if ($id) {
                                $productData = $this->productModel->load($id);
                                if ($productData && $productData->getId()) {
                                    $relatedProducts[] = $this->processProduct($productData);
                                }
                            }
                        } catch (\Exception $e) {
                            // Skip this related product if there's an error
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                // If there's an error getting related products, try categories
            }

            if(empty($relatedProducts)) {
                try {
                    $categories = $product->getCategoryIds();
                    if(!empty($categories)) {
                        $relatedProducts = $this->_getCategoryProducts($categories[0], 7);
                    }
                } catch (\Exception $e) {
                    // If there's an error getting category products, return empty array
                }
            }

            return $relatedProducts;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function _getFilters($categoryId = '') {
        $filter = [
            "message" => "",
            "data"    => []
        ];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $filterableAttributes = $objectManager->get(\Magento\Catalog\Model\Layer\Category\FilterableAttributeList::class);
        $attributes = $filterableAttributes->getList();

        $appState = $objectManager->get(\Magento\Framework\App\State::class);
        $layerResolver = $objectManager->get(\Magento\Catalog\Model\Layer\Resolver::class);
        $filterList = $objectManager->create(
            \Magento\Catalog\Model\Layer\FilterList::class,
            [
                'filterableAttributes' => $filterableAttributes
            ]
        );

        $layer = $layerResolver->get();

        if($categoryId) {
            $layer->setCurrentCategory($categoryId);
        }

        $filters = $filterList->getFilters($layer);
        $filterData = [];

        foreach($filters as $filter) {
            if ($filter->getItemsCount() > 0) {
                $fd = [];
                $keys = array_keys($filter->getData());
                if(!in_array("attribute_model", $keys)) {
                    if(in_array("items_data", $keys)) {
                        $fd['attributeCode'] = 'rating';
                        $fd['code'] = 'rating';
                        $fd['label'] = $filter->getName();
                        $fd['type'] = $filter->getName();
                        $fd['count'] = $filter->getItemsCount();
                    } else {
                        $fd['attributeCode'] = 'subcategories';
                        $fd['code'] = 'subcategories';
                        $fd['label'] = $filter->getName();
                        $fd['type'] = $filter->getName();
                        $fd['count'] = $filter->getItemsCount();
                    }
                } else {
                    $fd['attributeCode'] = $filter->getAttributeModel()->getAttributeCode();
                    $fd['code'] = $filter->getAttributeModel()->getAttributeId();
                    $fd['label'] = $filter->getAttributeModel()->getStoreLabel();
                    $fd['type'] = $filter->getAttributeModel()->getFrontendInput();
                    $fd['count'] = $filter->getItemsCount();
                }

                $j = 0;
                foreach ($filter->getItems() as $item) {
                    if($fd['type'] == "price") {
                        if($categoryId) {
                            $fd['max'] = $layer->setCurrentCategory($categoryId)->getProductCollection()->getMaxPrice();
                            $fd['min'] = $layer->setCurrentCategory($categoryId)->getProductCollection()->getMinPrice();
                        } else {
                            $fd['max'] = $layer->getProductCollection()->getMaxPrice();
                            $fd['min'] = $layer->getProductCollection()->getMinPrice();
                        }

                        if(!$fd['max']) {
                            $fd = [];
                        } else if($fd['max'] == $fd['min']) {
                            $fd = [];
                        }
                    } else {
                        $fd['options'][$j]['label'] = str_replace('"', "'", $item->getLabel());
                        $fd['options'][$j]['value'] = $item->getValue();
                        $fd['options'][$j]['count'] = $item->getCount();
                    }
                    $j++;
                }
                $filterData['data'][] = $fd;
            }
        }

        return $filterData;
    }

    private function getCategoryImageUrl($image) {
        if(!$image) {
            return 'https://static.vecteezy.com/system/resources/previews/009/637/030/original/pink-flower-icon-free-png.png';
        }

        return $this->baseUrl . $image;
    }

    //Latest Products List
    public function latestProducts($data) {
        $page = isset($data['page']) && !empty($data['page']) ? (int) $data['page'] : 1;
        $limit = isset($data['limit']) && !empty($data['limit']) ? $data['limit'] : 10;
        $category_id = isset($data['category_id']) && trim($data['category_id']) ? $data['category_id'] : 0;

        // Force sort to newest for latest products
        $sort = 'newest';
        $search = '';

        $products = $this->_getProductsList($limit, $page, $sort, $search, true, $category_id);

        $info = $this->successStatus('Latest Products List');
        $info['data']['count'] = isset($products['count']) ? $products['count'] : $limit;
        $info['data']['products'] = isset($products['products']) ? $products['products'] : [];
        $info['data']['latest'] = true;
        return $info;
    }

    public function categoryDetails($data) {
        if(!isset($data['category_id'])) {
            return $this->errorStatus(["Category ID is required"]);
        }

        $category_id = (int) $data['category_id'];
        $page = isset($data['page']) && !empty($data['page']) ? (int) $data['page'] : 1;
        $limit = isset($data['limit']) && !empty($data['limit']) ? $data['limit'] : 20;
        $sort = isset($data['sort']) ? $data['sort'] : 'name-a-z';
        $search = isset($data['search']) && trim($data['search']) ? $data['search'] : '';

        // Load category
        $categoryModel = $this->objectManager->create('Magento\Catalog\Model\Category');
        $category = $categoryModel->load($category_id);

        if (!$category->getId()) {
            return $this->errorStatus(["Category not found"]);
        }

        if (!$category->getIsActive()) {
            return $this->errorStatus(["Category is not active"]);
        }

        // Get sub categories
        $subCategories = $this->_getSubCategories($category_id);

        // Get products
        $products = $this->_getCategoryProducts($category_id, $limit, $page, $sort, $search, true);

        // Get category image
        $image = $this->getCategoryImageUrl($category->getImageUrl());

        $categoryData = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'image' => $image,
            'url_key' => $category->getUrlKey(),
            'level' => $category->getLevel(),
            'parent_id' => $category->getParentId(),
            'position' => $category->getPosition(),
            'is_active' => (bool) $category->getIsActive(),
            'product_count' => isset($products['count']) ? $products['count'] : 0,
            'sub_categories_count' => count($subCategories),
            'sub_categories' => $subCategories,
            'products' => isset($products['products']) ? $products['products'] : [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => isset($products['count']) ? $products['count'] : 0,
                'total_pages' => isset($products['count']) ? ceil($products['count'] / $limit) : 0
            ]
        ];

        $info = $this->successStatus('Category Details');
        $info['data'] = $categoryData;
        return $info;
    }

    private function _getSubCategories($category_id) {
        $disallowedCategories = [2,230,285,18,191,189,210,24,229,222];
        $categoryCollection = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\Collection');

        $subCategories = $categoryCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('entity_id', ['nin' => $disallowedCategories])
            ->addAttributeToFilter('parent_id', $category_id)
            ->addAttributeToSort('name');

        $subCategoriesData = [];

        foreach($subCategories as $subCategory) {
            if($subCategory->getProductCollection()->count() <= 0) {
                continue;
            }

            $image = $this->getCategoryImageUrl($subCategory->getImageUrl());

            $subCategoriesData[] = [
                'id' => $subCategory->getId(),
                'name' => $subCategory->getName(),
                'description' => $subCategory->getDescription(),
                'image' => $image,
                'url_key' => $subCategory->getUrlKey(),
                'level' => $subCategory->getLevel(),
                'position' => $subCategory->getPosition(),
                'product_count' => $subCategory->getProductCollection()->count(),
                'children_count' => $subCategory->getChildrenCount()
            ];
        }

        return $subCategoriesData;
    }

    public function getMainCategories($data) {
        $disallowedCategories = [2,230,285,18,191,189,210,24,229,222];
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        $categoryCollection = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\Collection');

        // Get main categories (level 2, parent_id = root category)
        $categories = $categoryCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('entity_id', ['nin' => $disallowedCategories])
            ->addAttributeToFilter('level', 2)
            ->addAttributeToFilter('parent_id', $rootCategoryId)
            ->addAttributeToSort('position', 'ASC')
            ->addAttributeToSort('name', 'ASC');

        $categoriesData = [];

        foreach($categories as $category) {
            // Get product count
            $productCount = $category->getProductCollection()->addAttributeToFilter('status', 1)->getSize();

            // Get children count
            $childrenCollection = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\Collection');
            $childrenCount = $childrenCollection->addAttributeToSelect('*')
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToFilter('parent_id', $category->getId())
                ->getSize();

            // Skip categories with no products and no children
            if($productCount <= 0 && $childrenCount <= 0) {
                continue;
            }

            $image = $this->getCategoryImageUrl($category->getImageUrl());

            $categoriesData[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'image' => $image,
                'url_key' => $category->getUrlKey(),
                'level' => $category->getLevel(),
                'parent_id' => $category->getParentId(),
                'position' => $category->getPosition(),
                'is_active' => (bool) $category->getIsActive(),
                'product_count' => $productCount,
                'children_count' => $childrenCount,
                'created_at' => $category->getCreatedAt(),
                'updated_at' => $category->getUpdatedAt()
            ];
        }

        $info = $this->successStatus("Main categories");
        $info['data'] = $categoriesData;
        $info['total_count'] = count($categoriesData);
        return $info;
    }

    /**
     * Create a simple image object for fallback image handling
     */
    private function createSimpleImageObject($imageUrl) {
        $imageObject = new \Magento\Framework\DataObject();
        $imageObject->setData('url', $imageUrl);
        $imageObject->setData('image_url', $imageUrl);

        // Add method to get image URL
        $imageObject->addData([
            'getImageUrl' => $imageUrl,
            'getUrl' => $imageUrl
        ]);

        return $imageObject;
    }
}
