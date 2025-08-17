<?php
namespace Josequal\APIMobile\Controller\Cart;

class TestOptions extends \Josequal\APIMobile\Controller\Action\Action
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

        if (!isset($data['options1']) || !isset($data['options2'])) {
            $this->printResult([
                'status' => false,
                'message' => 'options1 and options2 are required',
                'data' => []
            ]);
            return;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Cart');

        $result = $model->testOptionsComparison($data['options1'], $data['options2']);

        $response = [
            'status' => true,
            'message' => 'Options comparison test',
            'data' => $result
        ];

        $this->printResult($response);
    }
}
