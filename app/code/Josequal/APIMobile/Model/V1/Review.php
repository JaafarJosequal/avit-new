<?php
namespace Josequal\APIMobile\Model\V1;

/**
 * Added by Yash
 * For rating and review related functions
 * Date: 27-10-2014
 */
class Review extends \Josequal\APIMobile\Model\AbstractModel {

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    protected $customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $review;
    protected $reviewFactory;
    protected $ratingFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Review\Model\Review $review,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->review = $review;
        $this->reviewFactory = $reviewFactory;
        $this->ratingFactory = $ratingFactory;
        parent::__construct($context, $registry, $storeManager, $eventManager);

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->rating = $this->objectManager->get('Magento\Review\Model\Rating');
        $this->catalogModel = $this->objectManager->get('Josequal\APIMobile\Model\V1\Catalog');
    }

    public function getReviews($data = []) {
        $page = isset($data['page']) ? $data['page'] : 1;
        $limit = isset($data['limit']) ? $data['limit'] : 20;
        $product_id = isset($data['product_id']) ? $data['product_id'] : 0;
        $store = isset($data['store']) ? $data['store'] : 0;

        if (empty($store)) {
            $store = $this->storeManager->getStore()->getId();
        }

        $reviews = [];
        $collection = $this->review->getCollection()
            ->addFieldToFilter('store_id', $store)
            ->addFieldToFilter('entity_pk_value', $product_id)
            ->addFieldToFilter('status_id', \Magento\Review\Model\Review::STATUS_APPROVED)
            ->setPageSize($limit)
            ->setCurPage($page - 1)
            ->setOrder('created_at', 'DESC');

        foreach ($collection->getItems() as $_collection) {
            $_review = [
                'product_id' => $product_id,
                'created_at' => $_collection->getCreatedAt(),
                'title' => $_collection->getTitle(),
                'detail' => $_collection->getDetail(),
                'nickname' => $_collection->getNickname(),
            ];

            $averageRating = 0;
            $votes = $_collection->getRatingVotes();
            if ($votes) {
                foreach ($votes as $vote) {
                    $averageRating += $vote->getValue();
                }

                if(($averageRating > "0") && ($votes->count() > "0")) {
                    $averageRating = round($averageRating / $votes->count(), 2);
                }
            }

            $_review['averageRating'] = $averageRating;
            $reviews[] = $_review;
        }

        $info = $this->successStatus('Reviews List');
        $info['data']['reviews'] = $reviews;
        return $info;
    }

    public function submitReview($data = null) {
        try {
            if(!isset($data['product_id']) || !$data['product_id']) {
                return $this->errorStatus(["Product Id is required"]);
            }

            if(!isset($data['title']) || !$data['title']) {
                return $this->errorStatus(["Title is required"]);
            }

            if(!isset($data['detail']) || !$data['detail']) {
                return $this->errorStatus(["Detail is required"]);
            }

            if(!isset($data['nickname']) || !$data['nickname']) {
                return $this->errorStatus(["Nickname is required"]);
            }

            $productId = $data['product_id'];
            $product = $this->_loadProduct($productId);

            if (!$product) {
                return $this->errorStatus('product_not_available');
            }

            // Check if customer already reviewed this product
            $existingReview = $this->review->getCollection()
                ->addFieldToFilter('entity_pk_value', $productId)
                ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
                ->addFieldToFilter('store_id', $this->storeManager->getStore()->getId())
                ->getFirstItem();

            if ($existingReview->getId()) {
                return $this->errorStatus('You have already reviewed this product');
            }

            // Create review
            $review = $this->reviewFactory->create();
            $review->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE))
                ->setEntityPkValue($productId)
                ->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING)
                ->setCustomerId($this->customerSession->getCustomerId())
                ->setStoreId($this->storeManager->getStore()->getId())
                ->setStores([$this->storeManager->getStore()->getId()])
                ->setNickname($data['nickname'])
                ->setTitle($data['title'])
                ->setDetail($data['detail'])
                ->setCreatedAt(date('Y-m-d H:i:s'));

            // Save review
            $review->save();

            // Save ratings if provided
            if (isset($data['ratings']) && is_array($data['ratings'])) {
                // Get rating options to map rating codes to IDs
                $ratingOptions = $this->_getRatingOptions($data);
                $ratingCodeToId = [];

                foreach ($ratingOptions as $ratingOption) {
                    $ratingCodeToId[$ratingOption['rating_code']] = $ratingOption['rating_id'];
                }

                foreach ($data['ratings'] as $ratingCode => $ratingValue) {
                    if (isset($ratingCodeToId[$ratingCode]) && $ratingValue) {
                        $ratingId = $ratingCodeToId[$ratingCode];

                        // Find the option ID based on rating value (1-5)
                        $optionId = $this->_getOptionIdByValue($ratingId, $ratingValue);

                        if ($optionId) {
                            try {
                                $rating = $this->ratingFactory->create();
                                $rating->setRatingId($ratingId)
                                    ->setReviewId($review->getId())
                                    ->setCustomerId($this->customerSession->getCustomerId())
                                    ->setOptionId($optionId)
                                    ->setStores([$this->storeManager->getStore()->getId()]);
                                $rating->save();
                            } catch (\Exception $e) {
                                $this->logger->error('Error saving rating: ' . $e->getMessage());
                                // Continue with other ratings even if one fails
                            }
                        }
                    }
                }
            }

            $info = $this->successStatus('Review submitted successfully');
            return $info;

        } catch (\Exception $e) {
            $this->logger->error('Review submission error: ' . $e->getMessage());
            return $this->errorStatus('Error submitting review: ' . $e->getMessage());
        }
    }

    protected function _loadProduct($productId) {
        if (!$productId) {
            return false;
        }

        $product = $this->objectManager->get('\Magento\Catalog\Model\Product');
        $product->load($productId);

        if (!$product->getId()) {
            return false;
        }

        return $product;
    }

    public function _getRatingOptions($data, $storeId = null) {
        if (!$storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        try {
            $ratingCollection = $this->rating->getResourceCollection()
                ->addFieldToFilter('entity_code', 'product')
                ->setOrder('position', 'ASC')
                ->addFieldToFilter('is_active', 1)
                ->load();

            $ratingOptions = [];
            foreach ($ratingCollection as $rating) {
                $ratingOptions[] = [
                    'rating_id' => $rating->getId(),
                    'rating_code' => $rating->getRatingCode(),
                    'rating_name' => $rating->getRatingName()
                ];
            }

            return $ratingOptions;
        } catch (\Exception $e) {
            $this->logger->error('Error getting rating options: ' . $e->getMessage());
            return [];
        }
    }

    protected function _getOptionIdByValue($ratingId, $ratingValue) {
        try {
            // For Magento, rating values are typically 1-5, and option IDs are sequential
            // We'll map the rating value directly to option ID
            $optionId = $ratingValue;

            // Validate that the option ID exists for this rating
            $rating = $this->rating->load($ratingId);
            if ($rating->getId()) {
                $options = $rating->getOptions();
                if ($options) {
                    foreach ($options as $option) {
                        if ($option->getValue() == $ratingValue) {
                            return $option->getOptionId();
                        }
                    }
                }
            }

            // If we can't find the exact option, return the rating value as option ID
            return $optionId;
        } catch (\Exception $e) {
            $this->logger->error('Error getting option ID: ' . $e->getMessage());
            return $ratingValue; // Return the rating value as fallback
        }
    }
}
