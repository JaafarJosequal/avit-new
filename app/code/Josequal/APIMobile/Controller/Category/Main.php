<?php
namespace Josequal\APIMobile\Controller\Category;

class Main extends \Josequal\APIMobile\Controller\Action\Action
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

        $info = $model->getMainCategories($data);

        $this->printResult($info);
    }
}
