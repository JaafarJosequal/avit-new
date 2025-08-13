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
        $this->auth();
        $data = $this->getData();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Catalog');

        $info = $model->productInfo($data);

        $this->printResult($info);
    }
}
