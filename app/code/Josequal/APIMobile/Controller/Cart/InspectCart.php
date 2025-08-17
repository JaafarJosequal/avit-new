<?php
namespace Josequal\APIMobile\Controller\Cart;

class InspectCart extends \Josequal\APIMobile\Controller\Action\Action
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context, $context->getRequest());
    }

    /**
     * Execute action
     *
     * @return void
     */
    public function execute()
    {
        // Check authentication
        $customerId = $this->auth();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $quote = $cart->getQuote();

        // Get all items (including hidden)
        $allItems = $quote->getAllItems();
        $visibleItems = $quote->getAllVisibleItems();

        $detailedItems = [];

        foreach ($allItems as $item) {
            $detailedItems[] = [
                'item_id' => $item->getItemId(),
                'product_id' => $item->getProductId(),
                'name' => $item->getName(),
                'qty' => $item->getQty(),
                'is_visible' => $item->getIsVisible(),
                'parent_item_id' => $item->getParentItemId(),
                'product_type' => $item->getProductType(),
                'options' => $this->extractItemOptions($item)
            ];
        }

        $result = [
            'status' => true,
            'message' => 'Cart Inspection Details',
            'data' => [
                'quote_id' => $quote->getId(),
                'total_all_items' => count($allItems),
                'total_visible_items' => count($visibleItems),
                'cart_qty' => $quote->getItemsQty(),
                'all_items' => $detailedItems,
                'visible_items_only' => array_filter($detailedItems, function($item) {
                    return $item['is_visible'];
                })
            ]
        ];

        $this->printResult($result);
    }

    /**
     * Extract item options
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     */
    private function extractItemOptions($item) {
        $options = [];

        try {
            if ($item->getBuyRequest()) {
                $buyRequest = $item->getBuyRequest();

                if ($buyRequest->getColor()) {
                    $options['color'] = $buyRequest->getColor();
                }
                if ($buyRequest->getSize()) {
                    $options['size'] = $buyRequest->getSize();
                }

                // Get all data from buy request
                $allData = $buyRequest->getData();
                foreach ($allData as $key => $value) {
                    if (!in_array($key, ['qty', 'uenc', 'form_key']) && !empty($value)) {
                        $options[$key] = $value;
                    }
                }
            }

            // Also check product options
            $productOptions = $item->getProductOptions();
            if (isset($productOptions['info_buyRequest'])) {
                $infoBuyRequest = $productOptions['info_buyRequest'];
                foreach ($infoBuyRequest as $key => $value) {
                    if (!empty($value) && !isset($options[$key])) {
                        $options[$key] = $value;
                    }
                }
            }

        } catch (\Exception $e) {
            $options['error'] = $e->getMessage();
        }

        return $options;
    }
}
