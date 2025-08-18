<?php
namespace Josequal\APIMobile\Controller\Address;

use Josequal\APIMobile\Controller\Action\Action;

class GetList extends Action {

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
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Address');
        $info = $model->getAddresses($data);

        $this->printResult($info);
    }
}
