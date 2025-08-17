<?php
namespace Josequal\APIMobile\Controller\Cart;

class Update extends \Josequal\APIMobile\Controller\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context, $context->getRequest());
    }

    public function execute()
    {
        // Check authentication
        $customerId = $this->auth();

        $data = $this->getData();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Cart');

        $result = $model->updateCartItem($data);

        $this->printResult($result);
    }
}
