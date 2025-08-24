<?php
namespace Josequal\ImportExport\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Products Import/Export'));

        // Set the block class
        $resultPage->getLayout()->getBlock('content')->setChild(
            'josequal_import_export_products',
            $resultPage->getLayout()->createBlock(\Josequal\ImportExport\Block\Adminhtml\Products\Index::class)
        );

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Josequal_ImportExport::import_export_products');
    }
}
