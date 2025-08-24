<?php
namespace Josequal\ImportExport\Block\Adminhtml\Categories;

use Magento\Backend\Block\Template;

class Index extends Template
{
    protected $_template = 'Josequal_ImportExport::categories/index.phtml';

    public function getImportUrl()
    {
        return $this->getUrl('josequal_import_export/categories/import');
    }

    public function getExportUrl()
    {
        return $this->getUrl('josequal_import_export/categories/export');
    }

    public function getSampleCsvUrl()
    {
        return $this->getUrl('josequal_import_export/categories/downloadSample');
    }
}
