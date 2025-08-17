<?php
namespace Josequal\APIMobile\Controller\Cart;

class DebugCart extends \Josequal\APIMobile\Controller\Action\Action
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

        $data = $this->getData();

        if (!isset($data['action'])) {
            $this->printResult([
                'status' => false,
                'message' => 'Action is required (add, info, test_options)',
                'data' => []
            ]);
            return;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Cart');

        switch ($data['action']) {
            case 'add':
                $result = $model->addToCart($data);
                break;
            case 'info':
                $result = $model->getCartInfo();
                $result = [
                    'status' => true,
                    'message' => 'Cart Info',
                    'data' => $result
                ];
                break;
            case 'test_options':
                if (!isset($data['options1']) || !isset($data['options2'])) {
                    $result = [
                        'status' => false,
                        'message' => 'options1 and options2 are required for test_options',
                        'data' => []
                    ];
                } else {
                    $result = $model->testOptionsComparison($data['options1'], $data['options2']);
                    $result = [
                        'status' => true,
                        'message' => 'Options Comparison Test',
                        'data' => $result
                    ];
                }
                break;
            default:
                $result = [
                    'status' => false,
                    'message' => 'Invalid action. Use: add, info, test_options',
                    'data' => []
                ];
        }

        $this->printResult($result);
    }
}
