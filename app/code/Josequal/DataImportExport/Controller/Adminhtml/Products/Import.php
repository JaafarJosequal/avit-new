<?php
namespace Josequal\DataImportExport\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Josequal\DataImportExport\Model\Import\Product as ProductImport;

class Import extends Action
{
    protected $resultJsonFactory;
    protected $productImport;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductImport $productImport
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productImport = $productImport;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $file = $this->getRequest()->getFiles('import_file');

            if (!$file || !isset($file['tmp_name'])) {
                throw new \Exception(__('Please select a file to import.'));
            }

            $importResult = $this->productImport->importFromFile($file['tmp_name']);

            $result->setData([
                'success' => true,
                'message' => __('Import completed successfully. %1 products imported.', $importResult['imported_count']),
                'imported_count' => $importResult['imported_count'],
                'errors' => $importResult['errors']
            ]);
        } catch (\Exception $e) {
            $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        return $result;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Josequal_DataImportExport::products_import_export');
    }
}
