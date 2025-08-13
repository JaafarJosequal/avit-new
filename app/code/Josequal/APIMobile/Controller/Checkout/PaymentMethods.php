<?php
namespace Josequal\APIMobile\Controller\Checkout;

use Josequal\APIMobile\Controller\Action\Action;

class PaymentMethods extends Action {

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
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Checkout');
        $info = $model->getPaymentMethods($data);

        $this->printResult($info);
    }
}
