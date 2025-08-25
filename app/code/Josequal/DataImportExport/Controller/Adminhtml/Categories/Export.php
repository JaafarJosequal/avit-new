<?php
namespace Josequal\DataImportExport\Controller\Adminhtml\Categories;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Josequal\DataImportExport\Model\Export\CategoryExport;

class Export extends Action
{
    protected $fileFactory;
    protected $categoryExport;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        CategoryExport $categoryExport
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->categoryExport = $categoryExport;
    }

    public function execute()
    {
        try {
            $filters = $this->getRequest()->getParams();
            $exportData = $this->categoryExport->exportCategories($filters);

            $fileName = 'categories_export_' . date('Y-m-d_H-i-s') . '.csv';

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
        return $this->_authorization->isAllowed('Josequal_DataImportExport::categories_import_export');
    }
}
