<?php
namespace Josequal\DataImportExport\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Josequal\DataImportExport\Model\Export\Product as ProductExport;

class Export extends Action
{
    protected $fileFactory;
    protected $productExport;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        ProductExport $productExport
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->productExport = $productExport;
    }

    public function execute()
    {
        try {
            $filters = $this->getRequest()->getParams();
            $exportData = $this->productExport->exportProducts($filters);

            $fileName = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';

            return $this->fileFactory->create(
                $fileName,
                $exportData,
                \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/index');
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Josequal_DataImportExport::products_import_export');
    }
}
