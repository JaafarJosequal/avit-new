<?php
namespace Josequal\APIMobile\Controller\Cart;

class ApplyCoupon extends \Josequal\APIMobile\Controller\Action\Action
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

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Cart');

        $result = $model->applyCoupon($data);

        $this->printResult($result);
    }
}
