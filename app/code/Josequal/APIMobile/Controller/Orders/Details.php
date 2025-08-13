<?php
namespace Josequal\APIMobile\Controller\Orders;

use Josequal\APIMobile\Controller\Action\Action;

class Details extends Action {

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request
    ) {
        parent::__construct($context, $request);
    }

    public function execute() {
        $this->auth();
        $data = $this->getData();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Order');
        $info = $model->getOrderDetail($data);

        $this->printResult($info);
    }
}
