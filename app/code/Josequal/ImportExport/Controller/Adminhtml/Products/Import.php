<?php
namespace Josequal\ImportExport\Controller\Adminhtml\Products;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Import extends Action
{
    protected $resultJsonFactory;
    protected $uploaderFactory;
    protected $directoryList;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        UploaderFactory $uploaderFactory,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'import_file']);
            $uploader->setAllowedExtensions(['csv', 'xlsx']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->directoryList->getPath(DirectoryList::MEDIA);
            $uploadPath = $mediaDirectory . '/import_export/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $uploader->save($uploadPath);
            $fileName = $uploader->getUploadedFileName();

            // هنا يمكن إضافة منطق معالجة الملف
            $message = __('File %1 uploaded successfully', $fileName);

            return $result->setData([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Josequal_ImportExport::import_export_products');
    }
}
