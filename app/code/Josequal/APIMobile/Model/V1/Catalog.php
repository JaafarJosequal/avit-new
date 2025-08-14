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
        \Magento\Framework\Filesystem\DirectoryList $dir
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
        $this->imageBuilder = $this->objectManager->get('\Magento\Catalog\Block\Product\ImageBuilder');
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
        $model = $this->objectManager->get('\Magento\Catalog\Model\Category');

        $productsQuery = $model->load($category_id)->getProductCollection()
                ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', '1')
                ->addAttributeToFilter('visibility', '4')
                ->setStoreId($this->_getStoreId())
                ->addMinimalPrice()
                ->addFinalPrice();

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

    //Product Details
    public function productInfo($data) {
        if(!isset($data['product_id'])) {
            return $this->errorStatus(["Product Id is required"]);
        }

        $storeId = $this->storeManager->getStore()->getId();
        $productId = $data['product_id'];
        $product = $this->_productLoader->create()->load($productId);

        if ($product->getId()) {
            $productWebsiteIds = $product->getWebsiteIds();
            $currentWebsiteId = $this->storeManager->getStore()->getWebsiteId();

            if (!in_array($currentWebsiteId, $productWebsiteIds)) {
                return $this->errorStatus('product_not_available');
            }

            $_product = $this->_productInfo($product);

            $info = $this->successStatus('Product Details');
            $info['data'] = $_product;
            return $info;
        }

        return $this->errorStatus('product_not_available');
    }

    function _productInfo($product) {
        try {
            $storeId = $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            $storeId = 1; // Default store ID
        }

        $catalogHelper = $this->objectManager->get('\Magento\Catalog\Helper\Product');
        $dataHelper = $this->objectManager->get('\Magento\Catalog\Helper\Data');
        $reviewModel = $this->objectManager->get('\Josequal\APIMobile\Model\V1\Review');

        try {
            $currency_code = $this->storeManager->getStore()->getCurrentCurrencyCode();
        } catch (\Exception $e) {
            $currency_code = 'USD'; // Default currency
        }

        $getBlockByIdentifier = $this->objectManager->get('\Magento\Cms\Api\GetBlockByIdentifierInterface');

        $outputHelper = $this->objectManager->get('Magento\Catalog\Helper\Output');
        $imageHelper = $this->objectManager->get('Magento\Catalog\Helper\Image');

        $images = [];
        try {
            $mediaGalleryImages = $product->getMediaGalleryImages();
            if ($mediaGalleryImages) {
                foreach ($mediaGalleryImages as $image) {
                    $images[] = $image->getUrl();
                }
            }
        } catch (\Exception $e) {
            $images = [];
        }

        if ($product->getTypeId() == 'configurable') {
            try {
                $configurable_images = [];
                $associated_products = $product->loadByAttribute('sku', $product->getSku())->getTypeInstance()->getUsedProducts($product);

                if (count($associated_products) > 0) {
                    foreach ($associated_products as $key => $ap) {
                        if ($key > 0) {
                            continue;
                        }

                        $ap = $this->productModel->setStoreId($storeId)->load($ap->getId());
                        foreach ($ap->getMediaGalleryImages() as $image) {
                            $images[] = $image->getUrl();
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
            $_product['description'] = $outputHelper->productAttribute($product, $product->getShortDescription(), 'short_description') ?? '';
        } catch (\Exception $e) {
            $_product['description'] = '';
        }

        try {
            $_product['care_tips'] = strip_tags(html_entity_decode($getBlockByIdentifier->execute('care-tips', $storeId)->getContent()));
        } catch (\Exception $e) {
            $_product['care_tips'] = '';
        }

        try {
            $_product['options'] = $this->formatCartOptions($product->getOptions());
        } catch (\Exception $e) {
            $_product['options'] = [];
        }

        try {
            $reviews = $reviewModel->getReviews([
                'page' => 1,
                'limit' => 100,
                'product_id' => $product->getId(),
                'store' => $storeId,
            ]);
            $_product['reviews'] = isset($reviews['data']['reviews']) ? $reviews['data']['reviews'] : [];
        } catch (\Exception $e) {
            $_product['reviews'] = [];
        }

        try {
            $_product['attributes'] = $this->getCustomProductAttributes($product);
        } catch (\Exception $e) {
            $_product['attributes'] = [];
        }

        try {
            $_product['related'] = $this->getRelatedProducts($product);
        } catch (\Exception $e) {
            $_product['related'] = [];
        }

        return $_product;
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

        // Safely get quantity
        $qty = 0;
        try {
            if ($productId > 0) {
                $quantity = $this->stockState->getStockItem($productId);
                if ($quantity && $quantity->getId()) {
                    $qty = (float) $quantity->getQty() ?: 0;
                }
            }
        } catch (\Exception $e) {
            $qty = 0;
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
            $image = $this->getImage($product, 'product_page_image_large');
            if ($image) {
                $imageUrl = $image->getImageUrl();
            }
        } catch (\Exception $e) {
            // Use default image or empty string
            $imageUrl = '';
        }

        // Safely format currency
        $formattedPrice = '';
        $formattedSpecialPrice = '';
        $formattedLowestPrice = '';

        try {
            $formattedPrice = $this->currencyHelper->currency($finalPrice, true, false);
            $formattedSpecialPrice = $this->currencyHelper->currency($finalPrice, true, false);
            $formattedLowestPrice = $this->currencyHelper->currency($minPrice, true, false);
        } catch (\Exception $e) {
            $formattedPrice = number_format($finalPrice, 2);
            $formattedSpecialPrice = number_format($finalPrice, 2);
            $formattedLowestPrice = number_format($minPrice, 2);
        }

        // Safely check stock status
        $stockStatus = false;
        try {
            // Check if product is saleable and available, but also consider quantity
            if ($qty > 0) {
                $stockStatus = $product->isSaleable() && $product->isAvailable();
            } else {
                $stockStatus = false; // If quantity is 0, stock status should be false
            }
        } catch (\Exception $e) {
            // If there's an error checking stock status, set it based on quantity
            $stockStatus = ($qty > 0);
        }

        // Safely get product name and SKU
        $productName = '';
        $productSku = '';
        $productType = '';

        try {
            $productName = $product->getName() ?: '';
            $productSku = $product->getSku() ?: '';
            $productType = $product->getTypeId() ?: '';
        } catch (\Exception $e) {
            $productName = '';
            $productSku = '';
            $productType = '';
        }

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
            'review_summary' => $this->getReviewSummary($product),
            'image' => $imageUrl,
            'has_discount' => $difference > 0,
            'discount' => $discountPercentage . '%',
            'is_favorite' => $this->productInFav($productId)
        ];
    }

    //Get Product image from cache
    private function getImage($product, $imageId, $attributes = []) {
        try {
            return $this->imageBuilder->setProduct($product)->setImageId($imageId)->setAttributes($attributes)->create();
        } catch (\Exception $e) {
            // Return a default image if there's an error
            return null;
        }
    }

    //Get products Review Summary
    private function getReviewSummary($product) {
        try {
            $this->_reviewFactory->create()->getEntitySummary($product, $this->_getStoreId());

            $summary = $product->getRatingSummary()->getRatingSummary();
            $averageRating = round($summary * 0.05, 1);
            $data = [
                'count' => (int) $product->getRatingSummary()->getReviewsCount() ?? 0,
                'summary' => (int) $summary ?? 0, // out of 100
                'averageRating' => (int) $averageRating, // out of 5
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
        switch ($sort) {
            case "position":
                $productsQuery->setOrder('relevance', 'ASC');
                break;
            case "price-l-h":
                $productsQuery->setOrder('price', 'asc');
                break;
            case "price-h-l":
                $productsQuery->setOrder('price', 'desc');
                break;
            case "rating-h-l":
                $productsQuery->setOrder('rating_summary', 'desc');
                break;
            case "rating-l-h":
                $productsQuery->setOrder('rating_summary', 'asc');
                break;
            case "name-a-z":
                $productsQuery->setOrder('name', 'asc');
                break;
            case "name-z-a":
                $productsQuery->setOrder('name', 'desc');
                break;
            case "newest":
                $productsQuery->setOrder('created_at', 'desc');
                break;
            case "oldest":
                $productsQuery->setOrder('created_at', 'asc');
                break;
            default:
                $productsQuery->setOrder('name', 'asc');
                break;
        }
        return $productsQuery;
    }

    //format Product options
    private function formatCartOptions($options) {
        $data = [];
        foreach ($options as $option) {
            $optionData = [
                'option_id' => $option->getOptionId(),
                'title' => $option->getTitle(),
                'type' => $option->getType(),
                'values' => []
            ];

            if ($option->getValues()) {
                foreach ($option->getValues() as $value) {
                    $optionData['values'][] = [
                        'option_type_id' => $value->getOptionTypeId(),
                        'title' => $value->getTitle(),
                        'price' => $value->getPrice()
                    ];
                }
            }

            $data[] = $optionData;
        }
        return $data;
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

    public function getRelatedProducts($product) {
        $storeId = $this->storeManager->getStore()->getId();
        $relatedProductIds = $product->getRelatedProductCollection()->setPositionOrder();

        $relatedProducts = [];
        if ($relatedProductIds) {
            foreach ($relatedProductIds as $_relatedProductId) {
                $id = $_relatedProductId->getEntityId();
                $productData = $this->productModel->load($id);
                $relatedProducts[] = $this->processProduct($productData);
            }
        }

        if(empty($relatedProducts)) {
            $categories = $product->getCategoryIds();
            if(!empty($categories)) {
                $relatedProducts = $this->_getCategoryProducts($categories[0], 7);
            }
        }

        return $relatedProducts;
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
}
