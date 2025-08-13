<?php
namespace Josequal\APIMobile\Controller\Product;

class Latest extends \Josequal\APIMobile\Controller\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context, $context->getRequest());
    }

    public function execute()
    {
        $data = $this->getData();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Josequal\APIMobile\Model\V1\Catalog');

        $info = $model->latestProducts($data);

        $this->printResult($info);
    }
}
