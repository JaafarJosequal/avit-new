<?php
namespace Josequal\APIMobile\Controller\Product;

class Details extends \Josequal\APIMobile\Controller\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context, $context->getRequest());
    }

    public function execute()
    {
        try {
            // Remove authentication requirement - product details should be accessible to all users
            // $this->auth();
            $data = $this->getData();

            // Validate required parameters
            if (!isset($data['product_id']) || empty($data['product_id'])) {
                return $this->printResult([
                    'status' => false,
                    'message' => 'Product ID is required',
                    'data' => [],
                    'error_code' => 'MISSING_PRODUCT_ID'
                ]);
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $model = $objectManager->create('Josequal\APIMobile\Model\V1\Catalog');

            if (!$model) {
                return $this->printResult([
                    'status' => false,
                    'message' => 'Failed to create catalog model',
                    'data' => [],
                    'error_code' => 'MODEL_CREATION_FAILED'
                ]);
            }

            $info = $model->productInfo($data);

            // Validate response
            if (!$info || !is_array($info)) {
                return $this->printResult([
                    'status' => false,
                    'message' => 'Invalid response from catalog model',
                    'data' => [],
                    'error_code' => 'INVALID_MODEL_RESPONSE'
                ]);
            }

            return $this->printResult($info);

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Product not found
            return $this->printResult([
                'status' => false,
                'message' => 'Product not found',
                'data' => [],
                'error_code' => 'PRODUCT_NOT_FOUND',
                'debug_info' => $e->getMessage()
            ]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Magento specific exceptions
            return $this->printResult([
                'status' => false,
                'message' => $e->getMessage() ?: 'Product operation failed',
                'data' => [],
                'error_code' => 'MAGENTO_EXCEPTION',
                'debug_info' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Generic exceptions
            return $this->printResult([
                'status' => false,
                'message' => 'An unexpected error occurred',
                'data' => [],
                'error_code' => 'UNEXPECTED_ERROR',
                'debug_info' => $e->getMessage()
            ]);
        }
    }
}
